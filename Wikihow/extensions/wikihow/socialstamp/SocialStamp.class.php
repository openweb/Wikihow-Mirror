<?php

class SocialStamp {
	const MIN_AUTHOR = 9;
	const MIN_VIEWS = 1000;
	const KEY_RECIPE_BYLINE_TAG = "recipe_byline_eligible";

	private static $verifiers = [];

	private static $hoverText = "";
	private static $authorInfoText = "";
	private static $byLineHtml = "";
	private static $isNotable = null;

	const NOTABLE_TAG = "notable_coauthor";
	const ENHANCED_BYLINE_ARTICLE_TAG = 'enhanced_byline_test';
	const ENHANCED_BYLINE_EXPERTS_TAG = 'enhanced_byline_experts';

	// for testing - allows us to run processDom multiple times with same result
	public static function resetStaticVarsForTesting() {
		//self::$byLineHtml = "";
		//self::$hoverText = "";
		self::$authorInfoText = "";
	}

	public static function addDesktopByline($out) {
		if (!self::isEligibleForByline()) return;

		$html = self::getBylineHtml();

		pq('.firstHeading')->after($html);

		if (class_exists('TechRating')) {
			$techRating = TechRating::techRatingHtml( $out->getTitle()->getArticleID() );
			pq('.firstHeading')->before( $techRating );
		}
	}

	private static function getBylineHtml() {
		if (self::$byLineHtml == "") {
			self::setBylineVariables();
		}

		return self::$byLineHtml;
	}

	private static function getHoverText() {
		if (self::$hoverText == "") {
			self::setBylineVariables();
		}

		return self::$hoverText;
	}

	private static function getAuthorInfoText() {
		if (self::$authorInfoText == "") {
			self::setBylineVariables();
		}

		return self::$authorInfoText;
	}

	private static function setBylineVariables() {
		$out = RequestContext::getMain()->getOutput();
		$isAmp = GoogleAmp::isAmpMode($out);
		$isMobile = Misc::isMobileMode();
		$articleId = $out->getTitle()->getArticleId();
		Hooks::run( 'BylineStamp', [ &self::$verifiers, $articleId ] );

		$params = self::setBylineData(self::$verifiers, $articleId, $isMobile, $isAmp, AlternateDomain::onAlternateDomain());
		$template = $isMobile ? 'mobile_byline.mustache' : 'desktop_byline.mustache';
		$html = self::getHtmlFromTemplate($template, $params);

		self::$hoverText = $params['body'];
		self::$authorInfoText = $params['author_info_text'];
		self::$byLineHtml = $html;
	}

	public static function getHoverTextForArticleInfo(){
		$text = trim(self::getHoverText());
		$brLoc = stripos($text, "<br");
		if ($brLoc !== false) {
			$text = substr($text, 0, $brLoc);
		} else {
			$learnmoreLoc = strripos($text, "</a>");
			if ($learnmoreLoc == strlen($text) - 4) {
				//remove the learn more
				$text = substr($text, 0, strripos($text, "<a"));
			}
		}

		return $text;
	}

	public static function getAuthorInfoTextforArticleInfo(){
		$text = trim(self::getAuthorInfoText());
		$brLoc = stripos($text, "<br");
		if ($brLoc !== false) {
			$text = substr($text, 0, $brLoc);
		} else {
			$learnmoreLoc = strripos($text, "</a>");
			if ($learnmoreLoc == strlen($text) - 4) {
				//remove the learn more
				$text = substr($text, 0, strripos($text, "<a"));
			}
		}

		return $text;
	}

	public static function addMobileByline(&$data){
		if (!self::isEligibleForByline()) return;

		Hooks::run( 'BylineStamp', [ &self::$verifiers, $data['articleid'] ] );

		$html = self::getBylineHtml();

		$data['prebodytext'] .= $html;

		if(class_exists('TechRating') && !$data['amp']) {
			$techRating = TechRating::techRatingHtml( $data['articleid'] );
			$data['prebodytext'] = $techRating . $data['prebodytext'];
		}
	}

	private static function isEligibleForByline() {
		$main = RequestContext::getMain();
		$req = $main->getRequest();

		$revision = $req->getVal('oldid', '');
		if ($revision != "") {
			return false;
		}

		$action = $req->getVal('action', 'view');
		if ( $action != 'view' ) {
			return false;
		}

		$title = $main->getTitle();
		if (!$title->inNamespace(NS_MAIN) || $title->isMainPage() || $title->getArticleID() <= 0) return false;

		if ($title->isRedirect()) return false;

		if (!PagePolicy::showCurrentTitle($main)) return false;

		return true;
	}

	public static function getCoauthorGroup(array $verifiers): string
	{
		if ( array_key_exists(SocialProofStats::VERIFIER_TYPE_EXPERT, $verifiers)) {
			return 'expert';
		} elseif ( array_key_exists(SocialProofStats::VERIFIER_TYPE_ACADEMIC, $verifiers)) {
			return 'expert';
		} elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_YOUTUBER, $verifiers)) {
			return 'expert';
		} elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_COMMUNITY, $verifiers)) {
			return 'community';
		} elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_STAFF, $verifiers)) {
			return 'staff';
		} else {
			return 'author info';
		}
	}

	private static function setBylineData($verifiers, $articleId, $isMobile, $isAmp, $isAlternateDomain) {
		$params = [];

		$group = self::getCoauthorGroup($verifiers);

		$isExpert =    $group == 'expert'; // expert | academic | youtuber
		$isCommunity = $group == 'community';
		$isStaff =     $group == 'staff';
		$isDefault =   $group == 'author info';

		$isTested = false; // tech | video | chef
		$isUserReview = false;
		$isIntl = Misc::isIntl();

		$hoverText = "";
		$authorInfoText = '';

		if ( ArticleTagList::hasTag("expert_test", $articleId) && !RequestContext::getMain()->getUser()->isLoggedIn() ) {
			if(isset($verifiers['expert'])) {
				$vd = VerifyData::getVerifierInfoById($verifiers['expert']->verifierId);
			} elseif (isset($verifiers['academic'])) {
				$vd = VerifyData::getVerifierInfoById($verifiers['academic']->verifierId);
			}
			$params['coauthor_image'] = $vd->imagePath;
		}

		$refsUrl = Misc::getReferencesID();

		$params["coauthor"] = wfMessage('sp_expert_attribution')->text();
		$params["connector"] = "<span class='ss_pipe'>|</span>";
		$params['check'] = "ss_check";

		$refsCount = Misc::getReferencesCount();
		$minCitations = $isMobile ? SocialProofStats::DISPLAY_CITATIONS_LIMIT_MOBILE : SocialProofStats::DISPLAY_CITATIONS_LIMIT;
		$hasEnoughRefsForByline = $refsCount >= $minCitations;

		$params['references_label'] = wfMessage('references')->text();
		$params['refsCount'] = $refsCount;
		$params['refsUrl'] = $refsUrl;
		$params['linkUrl'] = $isMobile ? "social_proof_anchor" : "article_info_section";

		if ( !$isIntl ) {
			$params['lastUpdatedMsg'] = wfMessage('ss_last_updated')->text();
			$params['lastUpdatedDate'] = self::lastUpdatedDate();
		}

		if ($isMobile) {
			$articleWithTabs = class_exists('MobileTabs') && MobileTabs::isTabArticle( RequestContext::getMain()->getTitle());
			$params['noTabs'] = $articleWithTabs ? '' : 'no_tabs';
		}

		// expert
		if ( array_key_exists(SocialProofStats::VERIFIER_TYPE_EXPERT, $verifiers) ) {
			$key = SocialProofStats::VERIFIER_TYPE_EXPERT;
		}
		// academic
		elseif ( array_key_exists(SocialProofStats::VERIFIER_TYPE_ACADEMIC, $verifiers) ) {
			$key = SocialProofStats::VERIFIER_TYPE_ACADEMIC;
		}
		// youtuber
		elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_YOUTUBER, $verifiers) ) {
			$key = SocialProofStats::VERIFIER_TYPE_YOUTUBER;
		}
		// community
		elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_COMMUNITY, $verifiers) ) {
			$key = SocialProofStats::VERIFIER_TYPE_COMMUNITY;
		}
		// staff
		elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_STAFF, $verifiers) ) {
			$key = SocialProofStats::VERIFIER_TYPE_STAFF;
			$params['slot1'] = self::getIntroInfo($key, $verifiers[$key]);
			$params['slot1class'] = "staff_icon";
			$params['showBylineRefs'] = $hasEnoughRefsForByline;
		}
		// default (author info)
		else {
			$params['slot1'] = self::GetIntroInfo(SocialProofStats::VERIFIER_TYPE_AUTHORS);
			unset($params["coauthor"]);
			$params['slot1class'] = "author_icon";
			$params["check"] = "ss_info";
			$key = $isIntl && $isMobile ? 'showBylineRefsNextToAuthor' : 'showBylineRefs';
			$params[$key] = $hasEnoughRefsForByline;
		}

		# First part (left) (slot1)
		if ($isExpert || $isCommunity) {
			$params['slot1'] = self::getIntroInfo($key, $verifiers[$key]);
			$params['slot1class'] = "expert_icon";
			if (SocialProofStats::isSpecialInline()) {
				$params['coauthor'] = wfMessage("ss_special_author")->text();
			}
			if (SocialProofStats::isMedicallyReviewed()) {
				$params['coauthor'] = wfMessage("ss_medical_author")->text();
			}
			if (SocialStamp::isNotable()) {
				$params['coauthor'] = wfMessage("ss_notable")->text();
			}

			if (self::showEnhancedByline($articleId, $verifiers[$key])) {
				//HACK - remove any leading PhD because it's at the end of 2 people's names
				$desc = preg_replace('/^PhD,\s/', '', $verifiers[$key]->blurb);
				$params['slot1_desc'] = $desc;
			}
		}
		# Second part (right) (slot2), only if no expert
		else {
			// tech
			if (array_key_exists(SocialProofStats::VERIFIER_TYPE_TECH, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_TECH;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_TECH);
				$params['slot2class'] = 'ss_tech';
				if ($isMobile) $params['showBylineRefs'] = false; //tech never shows references on mobile
				$isTested = true;
			}
			// video
			elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_VIDEO, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_VIDEO;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_VIDEO);
				$params['slot2class'] = 'ss_video';
				$isTested = true;
			}
			// chef
			elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_CHEF, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_CHEF;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_CHEF);
				$params['slot2class'] = 'ss_video';
				$isTested = true;
			}
			// user_review
			elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_READER, $verifiers)) {
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_approved')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_READER);
				$params['slot2class'] = 'ss_review';
				$hoverText .= UserReview::getIconHoverText($articleId);
				$isUserReview = true;
			}

			if ($isDefault && isset( $params['slot2_intro'] )) {
				$params['slot2_intro'] = ucfirst($params['slot2_intro']);
			}
		}

		// Show references
		WikihowToc::setReferences();

		# Hover text

		$citations = '';
		if ($refsCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
			if ($isExpert || $isCommunity || $isIntl) {
				$msg = 'ss_expert_citations';
			} elseif ($isStaff) {
				$msg = 'ss_staff_citations';
			} elseif ($isDefault) {
				$msg = 'ss_default_citations';
			}
			$citations = wfMessage($msg, $refsCount, $refsUrl)->text();
		}

		if ($isExpert) {
			$vData = $verifiers[$key];
			$link = ArticleReviewers::getLinkToCoauthor($vData);
			if ($isIntl) {
				if ( $vData->hoverBlurb ) { // show link only if blurb is translated
					$coauthorLink = Html::element('a', ['href' => $link], $vData->name);
					$coauthorNoLink = $vData->name;
					$msg = 'ss_coauthored_by';
				} else {
					$coauthorLink = $coauthorNoLink = $vData->name;
					$msg = 'ss_expert_no_blurb';
				}

				$hoverText = wfMessage($msg, $coauthorLink )->text() . ' ' . $vData->hoverBlurb . $citations;
				$authorInfoText = wfMessage($msg, $coauthorNoLink )->text() . ' ' . $vData->hoverBlurb . $citations;
			} else {
				$coauthoredBy = lcfirst(wfMessage("sp_expert_attribution")->text());
				if (SocialProofStats::isSpecialInline()) {
					$coauthoredBy = lcfirst(wfMessage("ss_special_author")->text());
				}
				if (SocialProofStats::isMedicallyReviewed()) {
					$coauthoredBy = lcfirst(wfMessage("ss_medical_author")->text());
				}
				$hoverText = wfMessage('ss_expert', $vData->name, $vData->hoverBlurb, $link, $citations, $coauthoredBy )->text();
				$authorInfoText = wfMessage('ss_expert_nolink', $vData->name, $vData->hoverBlurb, $link, $citations, $coauthoredBy )->text();
			}
		}
		elseif ($isCommunity) {
			$hoverText = $authorInfoText = wfMessage("ss_community", $verifiers[$key]->name, $verifiers[$key]->hoverBlurb, $citations)->text();
		}
		elseif ($isStaff) {
			if ($isTested) {
				$hoverText = $authorInfoText = wfMessage('ss_staff_tested', $citations, self::getHoverInfo($testKey))->text();
			} elseif ($isUserReview) {
				$hoverText = $authorInfoText = wfMessage('ss_staff_readers', $citations, UserReview::getIconHoverText($articleId))->text();
			} else {
				$hoverText = $authorInfoText = wfMessage('ss_staff', $citations)->text();
			}
		}
		elseif ($isDefault) {
			$numEditors = $isIntl
				? ArticleAuthors::getENAuthorCount($articleId)
				: count(ArticleAuthors::getAuthors($articleId));
			if ($numEditors >= self::MIN_AUTHOR) {
				$editorBlurb = wfMessage('ss_editors_big', $numEditors)->text();
			} else {
				$editorBlurb = wfMessage('ss_editors_small', $numEditors)->text();
			}

			if ($isIntl) {
				$hoverText = $authorInfoText = wfMessage('ss_default')->text() . ' ' . $editorBlurb . $citations;
			} else {
				$views = RequestContext::getMain()->getWikiPage()->getCount();
				if ($isTested) {
					$hoverText = $authorInfoText = wfMessage("ss_default_tested", $editorBlurb, $citations, self::getHoverInfo($testKey) )->text();
				} elseif ($isUserReview) {
					$hoverText = $authorInfoText = wfMessage("ss_default_readers", $editorBlurb, $citations, UserReview::getIconHoverText($articleId) )->text();
				} else {
					if ($views > self::MIN_VIEWS) {
						$viewText = wfMessage("ss_default_views", number_format($views))->text();
					}
					$hoverText = $authorInfoText = wfMessage('ss_default', $editorBlurb, $citations, $viewText)->text();
				}
			}
		}

		$params = array_merge($params, self::getIconHoverVars($hoverText, $authorInfoText, $isMobile, $isAmp, $isExpert, $isAlternateDomain));

		$params = self::initRecipeByline($params, $isExpert);

		if ($isAlternateDomain) {
			$altDomain = AlternateDomain::getAlternateDomainForCurrentPage();
			$params['altDomainClass'] = AlternateDomain::getAlternateDomainClass( $altDomain );
		}

		return $params;
	}

	private static function getHtmlFromTemplate($template, $data) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/../socialproof/templates'),
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates'),
			new Mustache_Loader_FilesystemLoader(__DIR__.'/reader_success_stories_dialog/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render($template, $data);
		return $html;
	}

	private static function getIconHoverVars(string $hover_text, string $author_info_text, bool $is_mobile, bool $amp, bool $isExpert, bool $isAlternateDomain) {
		$vars = [
			'header' => wfMessage('sp_hover_expert_header')->text(),
			'body' => $hover_text,
			'author_info_text' => $author_info_text,
			'mobile' => $is_mobile,
			'amp' => $amp
		];

		if (!Misc::isIntl() && !$isExpert && !$isAlternateDomain) {
			$vars['learn_more_link'] = SocialProofStats::LEARN_MORE_LINK;
			$vars['learn_more'] = wfMessage('sp_learn_more')->text();
		}

		return $vars;
	}

	public static function getIntroMessage($vType) {
		$introMessage = 'sp_intro_' . $vType;
		$message = wfMessage( $introMessage );
		return $message->exists() ? $message->text() : '';
	}

	private static function getIntroInfo($vType, $vData = null) {
		if (in_array($vType, [SocialProofStats::VERIFIER_TYPE_YOUTUBER, SocialProofStats::VERIFIER_TYPE_ACADEMIC, SocialProofStats::VERIFIER_TYPE_EXPERT])) {
			return $vData->name;
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_TECH) {
			return wfMessage("ss_tech_name")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_VIDEO) {
			return wfMessage("ss_video_name")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_READER) {
			return wfMessage("ss_reader_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_STAFF) {
			return wfMessage("ss_staff_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_AUTHORS) {
			return wfMessage("ss_author_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_CHEF) {
			return wfMessage("ss_chef_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_COMMUNITY) {
			return $vData->name;
		}
	}

	private static function getHoverInfo($vType, $Data = null) {
		if ( $vType == SocialProofStats::VERIFIER_TYPE_TECH) {
			return wfMessage("ss_tech_name_hover")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_VIDEO) {
			return wfMessage("ss_video_name_hover")->text();
		} if ($vType == SocialProofStats::VERIFIER_TYPE_CHEF) {
			return wfMessage("ss_chef_name_hover")->text();
		}
	}

	public static function isNotable() {
		if (!is_null(self::$isNotable)) return self::$isNotable;
		$context = RequestContext::getMain();
		$pageId = $context->getTitle()->getArticleId();
		self::$isNotable = ArticleTagList::hasTag(self::NOTABLE_TAG, $pageId);
		return self::$isNotable;
	}

	private static function lastUpdatedDate(): string {
		$context = RequestContext::getMain();
		$last_updated = '';

		$last_edit = $context->getWikiPage()->getTimestamp();
		if (!empty($last_edit)) {
			$ts = wfTimestamp( TS_MW, strtotime($last_edit) );
			$last_updated = $context->getLanguage()->sprintfDate('F j, Y', $ts);
		}

		return $last_updated;
	}

	private static function showEnhancedByline(Int $pageId, VerifyData $vData = null): Bool {
		return self::isEnhancedBylineArticle($pageId) || self::isEnhancedBylineExpert($vData);
	}

	private static function isEnhancedBylineArticle(int $pageId): Bool {
		return ArticleTagList::hasTag(self::ENHANCED_BYLINE_ARTICLE_TAG, $pageId);
	}

	private static function isEnhancedBylineExpert(VerifyData $vData = null): Bool {
		if (!isset($vData->verifierId) || RequestContext::getMain()->getUser()->isLoggedIn()) return false;

		$bucket = ConfigStorage::dbGetConfig(self::ENHANCED_BYLINE_EXPERTS_TAG, true);
		$expert_ids = explode("\n", $bucket);

		return in_array($vData->verifierId, $expert_ids);
	}

	private static function getTrustBanner(): String {
		$trust_page = wfMessage('trustworthy-page')->text();
		$link_page = Title::newFromText($trust_page, NS_PROJECT);

		$vars = [
			'banner_link' => $link_page && $link_page->exists() ? $link_page->getLocalURL() : '#',
			'banner_text' => wfMessage('ss_trust_banner_text')->parse()
		];

		return self::getHtmlFromTemplate('trust_banner.mustache', $vars);
	}

	private static function showTrustBanner(): Bool {
		$context = RequestContext::getMain();
		return self::isEligibleForByline() &&
			$context->getUser()->isAnon() &&
			$context->getLanguage()->getCode() == 'en' &&
			!AlternateDomain::onAlternateDomain();
	}

	public static function addDesktopTrustBanner() {
		if (!self::showTrustBanner()) return;
		$html = self::getTrustBanner();
		if ($html) pq('#intro')->prepend($html);
	}

	public static function addMobileTrustBanner(&$data) {
		if (!self::showTrustBanner()) return;
		$html = self::getTrustBanner();
		if ($html) $data['prebodytext'] = $html . $data['prebodytext'];
	}

	/**
	 * Only add this portion of the byline if it's a recipe article
	 *
	 * @param $params
	 * @return mixed
	 */
	private static function initRecipeByline($params, $isExpert) {
		// Don't show the recipe byline if there is an expert or it's not eligible
		if (self::isRecipeArticleBylineEligible()  && !$isExpert) {
			$out = RequestContext::getMain()->getOutput();
			$isAmp = GoogleAmp::isAmpMode($out);

			// Add the cta for recipe articles (but not on amp) - even if the recipe byline doesn't show due to below rules
			if (!$isAmp) {
				self::addReaderSuccessStoriesCTA();
			}

			$articleId = RequestContext::getMain()->getTitle()->getArticleID();
			$isMobile = Misc::isMobileMode();
			$parenttree = CategoryHelper::getCurrentParentCategoryTree();
			$fullCategoryTree = CategoryHelper::cleanCurrentParentCategoryTree($parenttree);
			$helpfulness = SocialProofStats::getHelpfulness($articleId, $fullCategoryTree, $isMobile);

			// Only show byline if there are >= 3 votes and a percentage of 80 or better
			$showRatingsByline = $helpfulness['count'] >= 3 && $helpfulness['value'] >= 80;
			if ($showRatingsByline) {
				$params['helpful'] = $helpfulness;
				$params['helpful_byline_noamp'] = !$isAmp;
				$params['helpful_byline_amp'] = $isAmp;
				$params['helpful_byline'] = 'sp_byline';
				$params['helpful_count_label'] = wfMessage('rss_helpful_count_label')->text();
				$params['showBylineRefs'] = false;
				// Only show the success stories message if there is at least 1 success story
				if (UserReview::getEligibleNumCuratedReviews($articleId) >= 1) {
					$params['show_success_stories'] = true;
					$params['success_stories_label'] = wfMessage('ss_success_stories_label')->text();
					$params['lastUpdatedDate'] = false;
				} else {
					$params['show_success_stories'] = false;
					// Show the last updated date for recipe bylines that don't have enough ratings to show
					// the "Success Stories" link
					if ($params['lastUpdatedDate']) {
						$params['recipesLastUpdatedDate'] = $params['lastUpdatedDate'];
						$params['lastUpdatedDate'] = false;
					}

				}
				// Add corresponding dialog for non-amp versions
				if (!$isAmp) {
					self::addReaderSuccessStoriesDialog();
				}
			}
		}
		return $params;
	}

	private static function addReaderSuccessStoriesDialog() {
		$out = RequestContext::getMain()->getOutput();
		$out->addModules('ext.wikihow.reader_success_stories_dialog');

		$data = [
			'ss_dialog_title' => wfMessage('rss_dialog_title')->text(),
			'ss_title' => wfMessage('rss_ss_title')->text(),
			'ss_share_title'  => wfMessage('rss_ss_share_title')->text(),
			'ss_share_button_title'  => wfMessage('rss_ss_share_button_title')->text(),
		];
		$html  = self::getHtmlFromTemplate('reader_success_stories_dialog', $data);
		$selector = Misc::isMobileMode() ? '#mw-content-text' : '#bodycontents';
		if (pq($selector)->length) {
			pq($selector)->append($html);
		}
	}

	private static function addReaderSuccessStoriesCTA() {
		$data = [
			'ss_share_title'  => wfMessage('rss_ss_share_title')->text(),
			'ss_share_button_title'  => wfMessage('rss_ss_share_button_title')->text(),
		];
		$html  = self::getHtmlFromTemplate('reader_success_stories_cta', $data);
		if (pq('.ingredients')->length) {
			pq('.ingredients')->after($html);
		}
	}

	private static function isRecipeArticleBylineEligible() {
		$t = RequestContext::getMain()->getTitle();
		$isTaggedArticle = ArticleTagList::hasTag(self::KEY_RECIPE_BYLINE_TAG, $t->getArticleID());
		return $isTaggedArticle ||
			($t->inNamespace(NS_MAIN) && CategoryHelper::isTitleInCategory($t, "Recipes"));
	}
}
