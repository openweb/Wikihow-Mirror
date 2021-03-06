<?php

if (!defined('MEDIAWIKI')) die();

use MediaWiki\MediaWikiServices;

class ArticleHooks {

	public static function onPageContentSaveUndoEditMarkPatrolled($wikiPage, $user, $content, $p4, $p5, $p6, $p7) {
		global $wgMemc, $wgRequest;

		$oldid = $wgRequest->getInt('wpUndoEdit');
		if ($oldid) {
			// using db master to avoid db replication lag
			$dbw = wfGetDB(DB_MASTER);
			$rcid = $dbw->selectField('recentchanges', 'rc_id', array('rc_this_oldid' => $oldid), __METHOD__);
			RecentChange::markPatrolled($rcid);
			PatrolLog::record($rcid, false);
		}

		// In WikiHowSkin.php we cache the info for the author line. we want to
		// remove this if that article was edited so that old info isn't cached.
		if ($wikiPage && class_exists('SkinWikihowskin')) {
			$cachekey = ArticleAuthors::getLoadAuthorsCachekey($wikiPage->getID());
			$wgMemc->delete($cachekey);
		}

		return true;
	}

	public static function updatePageFeaturedFurtherEditing($wikiPage, $user, $content, $summary, $flags) {
		if ($wikiPage) {
			$t = $wikiPage->getTitle();
			if (!$t || !$t->inNamespace(NS_MAIN)) {
				return true;
			}
		}

		$templates = explode("\n", wfMessage('templates_further_editing')->inContentLanguage()->text());
		$regexps = array();
		foreach ($templates as $template) {
			$template = trim($template);
			if ($template == "") continue;
			$regexps[] ='\{\{' . $template;
		}
		$re = "@" . implode("|", $regexps) . "@i";

		$wikitext = ContentHandler::getContentText($content);
		$updates = array();
		if (preg_match_all($re, $wikitext, $matches)) {
			$updates['page_further_editing'] = 1;
		}
		else{
			$updates['page_further_editing'] = 0; //added this to remove the further_editing tag if its no longer needed
		}
		if (preg_match("@\{\{fa\}\}@i", $wikitext)) {
			$updates['page_is_featured'] = 1;
		}
		if (sizeof($updates) > 0) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update('page', $updates, array('page_id'=>$t->getArticleID()), __METHOD__);
		}
		return true;
	}

	public static function updateArticleMetaInfo($wikiPage, $user, $content) {
		global $wgOut, $wgTitle, $wgServer;
		$title = $wikiPage->getTitle();
		$ami = new ArticleMetaInfo( $title );
		$lastVideoSrc = '';
		$summaryVideoSrc = '';
		$summaryVideoPoster = '';

		// Fake various things that a real web-request would expect - this is needed because we
		// call processBody (via WikihowArticleHTML::processArticleHTML) which is not designed to
		// be run outside of a normal web request, and this hook is sometimes called due to an edit
		// made by a maintenance script.
		if ( !isset( $wgServer ) ) {
			$wgServer = 'wikihow.com';
		}
		if ( !isset( $_SERVER['HTTP_HOST'] ) ) {
			$_SERVER['HTTP_HOST'] = $wgServer;
		}
		if ( !isset( $_SERVER['REQUEST_URI'] ) ) {
			$_SERVER['REQUEST_URI'] = '/' . $title->getDBKey();
		}
		if ( !isset( $wgTitle ) ) {
			$wgTitle = $title;
		}
		if ( !$wgOut->getContext()->getTitle() ) {
			$wgOut->getContext()->setTitle( $title );
		}
		$req = $wgOut->getContext()->getRequest();
		$url = null;
		try {
			// Throws exception when requestUrl is null, see FauxRequest.php
			$url = $req->getRequestURL();
		} catch ( Exception $e ) {
			$url = null;
		} finally {
			if ( $url === null ) {
				$req->setRequestUrl( $title->getDBKey() );
			}
		}

		// Only for main-site and main-namespace, otherwise we want them to be blank
		if (
			$title->inNamespace( NS_MAIN ) &&
			!( class_exists('AlternateDomain') &&
				(bool)AlternateDomain::getAlternateDomainForPage( $title->getArticleID() ) )
		) {
			try {
				// Parse new content
				$context = RequestContext::getMain();
				$context->setTitle( $title );
				$out = $context->getOutput();
				$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
				$options = $out->parserOptions();
				$options->setTidy( true );
				$options->setEditSection( false );
				$parserOutput = $out->parse(
					ContentHandler::getContentText( $content ),
					$title,
					$options
				);
				$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $content ) );
				$html = WikihowArticleHTML::processArticleHTML(
					$parserOutput,
					[ 'no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic ]
				);

				$doc = phpQuery::newDocument( $html );

				// Find the source URLs for the last video clip and the summary video
				foreach ( pq( 'video.m-video' ) as $video ) {
					// Inline summary video
					if (
						!pq( $video )->attr( 'data-summary' ) &&
						!pq( $video )->attr( 'id' ) !== 'summary_video_poster'
					) {
						// Inline video clip
						$lastVideoSrc = pq( $video )->attr( 'data-src' );
					}
				}
				$src = pq( '#summary_wrapper' )->attr( 'data-summary-video-src' );
				if ( $src ) {
					$summaryVideoSrc = $src;
				}
				$poster = pq( '#summary_wrapper' )->attr( 'data-summary-video-poster' );
				if ( $poster ) {
					$summaryVideoPoster = $poster;
				}

				$ami->loadInfo();

				$url = isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ?
					$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : '';

				if ( $ami->row['ami_summary_video'] != $summaryVideoSrc ) {
					// Log the change
					wfDebugLog( 'articlemetainfo', var_export( [
						'url' => $url,
						'page_title' => $title->getText(),
						'page_namespace' => $title->getNamespace(),
						'page_id' => $title->getArticleID(),
						'summary_video' => [
							'database' => $ami->row['ami_summary_video'],
							'content' => $summaryVideoSrc
						],
						'last_video' => [
							'database' => $ami->row['ami_video'],
							'content' => $lastVideoSrc
						],
						'html' => isset( $doc ) ? $doc->htmlOuter() : ''
					], true ) . "\n" );
				}

				// Update article meta info with their source URLs
				$ami->updateVideoPaths( $lastVideoSrc, $summaryVideoSrc );

				// Update video catalog

				if ( class_exists( 'VideoCatalog' ) ) {
					VideoCatalog::updateArticleLink(
						$title->getArticleID(),
						$summaryVideoSrc,
						$summaryVideoPoster,
						$lastVideoSrc
					);
				}
			} catch ( Exception $error ) {
				wfDebugLog( 'articlemetainfo', var_export( [
					'url' => $url,
					'page_title' => $title->getText(),
					'page_namespace' => $title->getNamespace(),
					'page_id' => $title->getArticleID(),
					'error' => $error->getMessage()
				], true ) . "\n" );
			}
		}

		return true;
	}

	public static function editPageBeforeEditToolbar(&$toolarray) {
		global $wgStylePath, $wgOut;

		$toolarray[] = [
			'image' => $wgStylePath . '/owl/images/1x1_transparent.gif',
			// Note that we use the tip both for the ALT tag and the TITLE tag of the image.
			// Older browsers show a "speedtip" type message only for ALT.
			// Ideally these should be different, realistically they
			// probably don't need to be.
			'tip' => 'Weave links',
			'open' => '',
			'close' => '',
			'sample' => '',
			'id' => 'weave_button',
		];


		$toolarray[] = [
			'image' => $wgStylePath . '/owl/images/1x1_transparent.gif',
			// Note that we use the tip both for the ALT tag and the TITLE tag of the image.
			// Older browsers show a "speedtip" type message only for ALT.
			// Ideally these should be different, realistically they
			// probably don't need to be.
			'tip' => 'Add Image',
			'open' => '',
			'close' => '',
			'sample' => '',
			'id' => 'imageupload_button',
		];

		// TODO, from Reuben: this RL module and JS/CSS/HTML should really be attached inside the
		//   EditPage::showEditForm:initial hook, which happens just before the edit form. Doing
		//   this hook work inside the edit form creates some pretty arbitrary restrictions (like
		//   the form-within-a-form problem).
		$wgOut->addModules('ext.wikihow.popbox');
		$popbox = PopBox::getPopBoxJSAdvanced();
		$popbox_div = PopBox::getPopBoxDiv();
		$wgOut->addHTML($popbox_div . $popbox);

		return true;
	}

	public static function onDoEditSectionLink($skin, $nt, $section, $tooltip, &$result, $lang) {
		$query = array();
		$query['action'] = "edit";
		$query['section'] = $section;

		//INTL: Edit section buttons need to be bigger for intl sites
		$editSectionButtonClass = "editsection";
		$customAttribs = array(
			'class' => $editSectionButtonClass,
			'onclick' => "gatTrack(gatUser,\'Edit\',\'Edit_section\');",
			'tabindex' => '-1',
			'title' => wfMessage('editsectionhint')->rawParams( htmlspecialchars($tooltip) )->escaped(),
			'aria-label' => wfMessage('aria_edit_section')->rawParams( htmlspecialchars($tooltip) )->showIfExists(),
		);

		$result = Linker::link( $nt, wfMessage('editsection')->text(), $customAttribs, $query, "known");

		return true;
	}

	/**
	 * Add global variables
	 */
	public static function addGlobalVariables(&$vars, $outputPage) {
		global $wgFBAppId, $wgGoogleAppId;
		$vars['wgWikihowSiteRev'] = WH_SITEREV;
		$vars['wgFBAppId'] = $wgFBAppId;
		$vars['wgGoogleAppId'] = $wgGoogleAppId;
		$vars['wgCivicAppId'] = WH_CIVIC_APP_ID;

		return true;
	}

	// Add to the list of available JS vars on every page
	public static function addJSglobals(&$vars) {
		$vars['wgCDNbase'] = wfGetPad('');
		$tree = CategoryHelper::getCurrentParentCategoryTree();
		$cats = CategoryHelper::cleanCurrentParentCategoryTree( $tree );
		$vars['wgCategories'] = $cats;
		return true;
	}

	public static function onDeferHeadScripts($outputPage, &$defer) {
		$ctx = $outputPage->getContext();
		if ($ctx->getTitle()->inNamespace(NS_MAIN)
			&& $ctx->getRequest()->getVal('action', 'view') == 'view'
			&& ! $ctx->getTitle()->isMainPage()
		) {
			$isMobileMode = Misc::isMobileMode();
			$defer = $isMobileMode;
		}
		return true;
	}

	public static function onArticleShowPatrolFooter() {
		return false;
	}

	public static function turnOffAutoTOC(&$parser) {
		$parser->mShowToc = false;

		return true;
	}

	public static function runAtAGlanceTest( $title ) {
		if ( class_exists( 'AtAGlance' ) ) {
			AtAGlance::runArticleHookTest( $title );
		}
		return true;
	}

	public static function firstEditPopCheck($page, $user) {
		global $wgLanguageCode;

		if ($wgLanguageCode != 'en') return true;

		$ctx = RequestContext::getMain();
		$title = $ctx->getTitle();
		if (!$title || !$title->inNamespace(NS_MAIN)) return true;

		$t = $page->getTitle();
		if (!$t || !$t->exists() || !$t->inNamespace(NS_MAIN)) return true;

		$first_edit = $user->isAnon() ? $_COOKIE['num_edits'] == 1 : $user->getEditCount() == 0;
		if (!$first_edit) return true;

		// it must have at least two revisions to show popup
		$dbr = wfGetDB(DB_REPLICA);
		$rev_count = $dbr->selectField('revision', 'count(*)', array('rev_page' => $page->getID()), __METHOD__);
		if ($rev_count < 2) return true;

		// set the trigger cookie
		$ctx->getRequest()->response()->setcookie('firstEditPop1', 1, time()+3600, array('secure') );

		return true;
	}

	public static function firstEditPopIt() {
		$ctx = RequestContext::getMain();
		$title = $ctx->getTitle();

		if ( $title && $title->inNamespace(NS_MAIN) && $ctx->getRequest()->getCookie( 'firstEditPop1' )  == 1 ) {
			$out = $ctx->getOutput();
			$out->addModules('ext.wikihow.first_edit_modal');
			//remove the cookie
			$ctx->getRequest()->response()->setcookie('firstEditPop1', 0, time()-3600, array('secure') );
		}
		return true;
	}

	// Run on NewRevisionFromEditComplete. It adds a tag to the first main namespace
	// edit done by a user with 0 contributions. Note that this is tag is not set
	// for anon users because they don't have a running contrib count.
	public static function onNewRevisionFromEditCompleteAddFirstContributionTag(
		$wikiPage,
		$revision,
		$originalRevId,
		$user,
		&$tags = null
	) {

		global $wgIgnoreNamespacesForEditCount;

		if( !$user || $user->isAnon() ) {
			return;
		}

		if ( !$wikiPage ) {
			return;
		}

		if ( in_array( $wikiPage->getTitle()->getNamespace(), $wgIgnoreNamespacesForEditCount ) ) {
			return;
		}

		if ( $user->getEditCount() == 0 ) {
			if ( is_array( $tags ) ) {
				$tags[] = "First Contribution from User";
			}
		}
	}

	// hook run when the good revision for an article has been updated
	public static function updateExpertVerifiedRevision( $pageId, $revisionId ) {
		$ok =  class_exists( 'ArticleVerifyReview' )
			&& class_exists( 'VerifyData' )
			&& VerifyData::isVerified( $pageId )
			&& VerifyData::isOKToPatrol( $pageId );
		if ($ok) {
			ArticleVerifyReview::addItem( $pageId, $revisionId );
		}
		return true;
	}

	public static function BuildMuscleHook($out) {
		$context = RequestContext::getMain();
		if ($context->getLanguage()->getCode() != 'en' || GoogleAmp::isAmpMode($out)) {
			return true;
		}

		$title = $out->getTitle();
		if ($title && $title->getArticleID() == 19958) {
			if (Misc::isMobileMode()) {
				pq("#intro")->after(wfMessage("Muscle_test_mobile")->text());
			} else {
				pq("#intro")->after(wfMessage("Muscle_test")->text());
			}
		}
		return true;
	}

	public static function addDesktopTOCItems($wgTitle, &$anchorList) {
		if ( Misc::isMobileMode() ) {
			return true;
		}

		$refId = Misc::getReferencesID();
		$refLabel = wfMessage('references')->text();
		$refCount = Misc::getReferencesCount();
		if ($refCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
			$anchorList[] = Html::rawElement('a', ['href'=>$refId, 'id'=>'toc_ref'], "$refCount $refLabel");
		} elseif( $refCount > 0 ) {
			$anchorList[] = Html::rawElement('a', ['href'=>$refId, 'id'=>'toc_ref'], $refLabel);
		}

		return true;
	}

	public static function onBeforeDisplayNoArticleInterface($article, $dir, $lang, $text, &$html) {
		$showHeader = false;

		$oldid = $article->getOldID();
		if ( $oldid ) {
			// don't change in this case
			return true;
		} elseif ( $article->getTitle()->getNamespace() === NS_MEDIAWIKI ) {
			// don't change in this case
			return true;
		} elseif ( $article->getTitle()->quickUserCan( 'create', $article->getContext()->getUser() )
			&& $article->getTitle()->quickUserCan( 'edit', $article->getContext()->getUser() )
		) {
			// changed inside this if condition for
			// custom messages and params needed for messages
			if ($article->getTitle()->getNamespace() == NS_USER_TALK) {
				$text = wfMessage( 'noarticletext_user_talk', $article->getTitle()->getText(), $article->getTitle()->getFullURL() . "?action=edit" )->plain();
				$showHeader = true;
			} elseif (MWNamespace::isTalk($article->mTitle->getNamespace())) {
				$text = wfMessage( 'noarticletext_talk', $article->getTitle()->getText(), $article->getTitle()->getFullURL() . "?action=edit"   )->plain();
				$showHeader = true;
			} elseif ($article->mTitle->getNamespace() == NS_USER) {
				$text = wfMessage( 'noarticletext_user', $article->getTitle()->getText(), $article->getTitle()->getFullURL() . "?action=edit"   )->plain();
			} elseif ($article->mTitle->getNamespace() == NS_USER_KUDOS) {
				$text = wfMessage( 'noarticletext_user_kudos', $article->getTitle()->getText(), $article->getTitle()->getFullURL() . "?action=edit"   )->plain();
			} elseif ($article->mTitle->getNamespace() == NS_MAIN) {
				$text = wfMessage( 'noarticletext', $article->getTitle()->getText(), $article->getTitle()->getFullURL() . "?action=edit", urlencode($article->getTitle()->getText())  )->plain();
				$showHeader = true;
			} else {
				$text = wfMessage( 'noarticletext_standard', $article->getTitle()->getDBKey(), $article->getTitle()->getText(), $article->getTitle()->getFullURL() . "?action=edit" )->plain();
			}
		} else {
			$text = wfMessage( 'noarticletext-nopermission' )->plain();
		}

		$text = "<div class='noarticletext'>\n$text\n</div>";

		// following 3 lines added for styling
		if ( $showHeader ) {
			$text = "<h2>" . wfMessage('noarticlefound')->text() . "</h2>$text";
		}

		// finally, change the input $html if needed
		$html = $text;
	}

	/**
	 * Limit max article length
	 */
	public static function onEditFilterMergedContent( $context, $content, $status, $summary, $user, $minoredit ) {
		$title = $context->getTitle();
		if ( ! $title->inNamespace(NS_MAIN) ) {
			return true;
		}
		if ( ! $content instanceof TextContent ) {
			return true;
		}

		$text = $content->getText();
		$len = mb_strlen($text);
		$max = 200000;
		if ( $len > $max ) {
			$status->fatal('editpage-error-text-too-long', $len, $max);
		}

		return true;
	}

}
