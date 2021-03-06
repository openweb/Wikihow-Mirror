<?php

class AlternateDomain {

	const RELATED_IMG_WIDTH = 360;
	const RELATED_IMG_HEIGHT = 231;

	private static $allPages = null;

	/*
	 * use this to check if a page is on the non branded domain or not
	 * @param int pageId
	 * @return string|null the domain the page is on
	 */
	private static function getNoBrandingDomainForPage( $pageId ) {
		$result = null;
		$noBrandingDomains = self::getNoBrandingDomains();
		foreach ( $noBrandingDomains as $domain ) {
			if ( ArticleTagList::hasTag( $domain, $pageId ) ) {
				$result = $domain;
			}
		}
		return $result;
	}

	/*
	 * use this to check if a page is on any alternate domain (branded or non branded)
	 * @param int pageId
	 * @return string|null the domain the page is on
	 */
	public static function getAlternateDomainForPage( $pageId ) {
		$result = self::getNoBrandingDomainForPage( $pageId );
		if ( $result ) {
			return $result;
		}

		$brandedAlternateDomains = self::getBrandedDomains();
		foreach ( $brandedAlternateDomains as $domain ) {
			if ( ArticleTagList::hasTag( $domain, $pageId ) ) {
				$result = $domain;
			}
		}
		return $result;
	}

	public static function getAllAlternateDomainPageIds() {
		$domains = self::getBrandedDomains();
		$ids = [];
		foreach ($domains as $domain) {
			$domainIds = self::getAlternateDomainPagesForDomain($domain);
			$ids = array_merge($ids, $domainIds);
		}

		return $ids;
	}

	/*
	 * get list of all alternate domains
	 */
	public static function getAlternateDomains() {
		return array_merge( self::getNoBrandingDomains(), self::getBrandedDomains() );
	}

	public static function getAlternateDomainClass($domain) {
		$domainMapping = [
			'howyougetfit.com' => 'howyougetfit',
			'howyoulivelife.com' => 'howyoulivelife',
			'wikihow.tech' => 'tech',
			'wikihow.pet' => 'pet',
			'wikihow.mom' => 'mom',
			'wikihow.life' => 'life',
			'wikihow.fitness' => 'fitness',
			'wikihow.health' => 'health',
			'wikihow-fun.com' => 'fun',
			'wikihow.legal' => 'legal',
		];

		if(array_key_exists($domain, $domainMapping)) {
			return $domainMapping[$domain];
		} else {
			"";
		}
	}

	/*
	 * get list of branded alt domains
	 */
	private static function getBrandedDomains() {
		return [ 'wikihow.tech', 'wikihow.pet', 'wikihow.mom', 'wikihow.life', 'wikihow.fitness', 'wikihow.health', 'wikihow-fun.com', 'wikihow.legal' ];
	}

	/*
	 * get list of no branding alt domains
	 */
	private static function getNoBrandingDomains() {
		return [ 'howyougetfit.com', 'howyoulivelife.com' ];
	}

	/*
	 * Create list of strings that we can use to check for other forms of
	 * alt domains on dev. For example, this creates a list of strings like:
	 *
	 * [ 'wikihowtech', 'wikihowfuncom', 'wikihowlife', ... ]
	 *
	 * This allows us to use domains like wikihowlife-reuben.wikidogs.com without
	 * having to change our fastly config, which is a little burdensome.
	 */
	private static function getDevAltDomainStrings() {
		global $wgIsDevServer;
		static $domains = [];
		if ( $wgIsDevServer && !$domains ) {
			foreach ( self::getAlternateDomains() as $realDomain ) {
				$devString = preg_replace( '@[-.]@', '', $realDomain );
				$domains[ $devString ] = $realDomain;
			}
		}
		return $domains;
	}

	/*
	 * are we on one of the alternate domains
	 */
	public static function onAlternateDomain() {
		$currentAltDomain = self::getAlternateDomainForCurrentPage();
		return ( $currentAltDomain ? true : false );
	}

	/*
	 * Returns the alternate domain we're on, false otherwise
	 */
	public static function getAlternateDomainForCurrentPage() {
		global $domainName;
		$domains = self::getAlternateDomains();
		foreach ( $domains as $domain ) {
			if ( strstr( $domainName, $domain ) ) {
				return $domain;
			}
		}
		$devAltDomain = self::devDomainToAltDomain($domainName);
		if ($devAltDomain) {
			return $devAltDomain;
		}
		return false;
	}

	/*
	 * are we on one of the no branding domains
	 */
	public static function onNoBrandingDomain() {
		global $domainName;
		$domains = self::getNoBrandingDomains();
		foreach ( $domains as  $domain ) {
			if ( strstr( $domainName, $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/*
	 * On dev, we look for a string like "wikihowtech" in the domain
	 * wikhowtech-p.wikidogs.com, then return "wikihow.tech".
	 */
	public static function devDomainToAltDomain($domainName) {
		global $wgIsDevServer;
		if (!$wgIsDevServer) {
			return '';
		}

		foreach ( self::getDevAltDomainStrings() as $devString => $altDomain ) {
			if ( strstr( $domainName, $devString ) !== false ) {
				return $altDomain;
			}
		}

		return '';
	}

	/*
	 * gets an array of all of the pages on the current alternate domain
	 * if you are on the regular wikihow domain it will return an empty array
	 * it uses config storage for lookup
	 */
	private static function getAlternateDomainPagesForCurrentDomain() {
		$domain = self::getCurrentRootDomain();
		$results = self::getAlternateDomainPagesForDomain($domain);
		return $results;
	}

	public static function getAlternateDomainPagesForDomain($rootDomain) {
		return explode( "\n", ConfigStorage::dbGetConfig( $rootDomain, true ) );
	}

	/*
	 * gets a list of all pages in all alternate domains
	 * uses config storage for lookup
	 * @return array list of page ids
	 */
	public static function getAllPages() {
		if ( self::$allPages != null ) {
			return self::$allPages;
		}

		$result = array();
		$domains = self::getAlternateDomains();
		foreach ( $domains as $domain ) {
			$config = ConfigStorage::dbGetConfig( $domain, true );
			if ($config) {
				$result += array_fill_keys( explode( "\n", $config ), $domain );
			}
		}
		self::$allPages = $result;
		return $result;
	}

	/*
	 * uses a url and a domain to create a url with a new domain
	 * keeping in mind mobile and dev site params
	 * @param $url the relative url to redirect to
	 * @param the domain to use for the url
	 * @return url with the new domain prefix
	 */
	private static function getDestUrl( $url, $domain ) {
		global $wgIsDevServer;

		$prefix = 'www.';
		if ( Misc::isMobileMode() ) {
			if ( $domain == "wikihow.com" ) {
				$prefix = 'm.';
			}
		}

		if ( $wgIsDevServer )  {
			$prefix = 'dev.';
			if ( $domain == "wikihow.com" ) {
				$prefix = "a."; // dev redirects to a.wikidogs.com
				$domain = "wikidogs.com";
			}
			if ( Misc::isMobileMode() ) {
				$prefix = 'dev.';
				if ( $domain == "wikihow.com" || $domain == "wikidogs.com" ) {
					$prefix = "a.m.";
				}
			}
		}

		$url = $prefix . $domain . $url;
		return $url;
	}


	/*
	 * This hook is responsible adding css to the output page specific for the alternate domains
	 */
	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$title = $out->getTitle();

		$isMainPage = $title
			&& $title->inNamespace( NS_MAIN )
			&& $title->getText() == wfMessage('mainpage')->inContentLanguage()->text();

		if ( Misc::isMobileMode() ) {
			if ( self::onNoBrandingDomain() ) {
				$style = Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.nobranding.mobile.css' );
				if ( $isMainPage ) {
					$style .= Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.nobranding.mainpage.mobile.css' );
				}
			} else {
				$style = Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.mobile.css' );
				if ( $isMainPage ) {
					$style .= Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.mainpage.mobile.css' );
				}
			}

			//custom Terms of Use page
			if ($title->inNamespace( NS_PROJECT ) && $title->getText() == 'Terms of Use') {
				$altDomain = self::getCurrentRootDomain();
				$pageTitle = $altDomain.' '.wfMessage('termsofuse')->text();
				$out->setPageTitle($pageTitle);

				$out->clearHTML();
				$out->addHTML( self::termsOfUseHTML() );
			}

		} else {
			if ( self::onNoBrandingDomain() ) {
				$style = Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.nobranding.desktop.css' );
				if ( $isMainPage ) {
					$style .= Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.mainpage.desktop.css' );
				}
			} else {
				$style = Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.desktop.css' );
				if ( $isMainPage ) {
					$style .= Misc::getEmbedFile( 'css', __DIR__ . '/alternatedomain.mainpage.desktop.css' );
				}

			}
		}

		if ( GoogleAmp::isAmpMode( $out ) ) {
			$items = $out->getHeadItemsArray();
			$topcss = null;
			foreach( $items as $key => $item ) {
				if ( $key == "topcss" ) {
					$topcss = $item;
					break;
				}
			}

			$topcss = str_replace( "</style>", "", $topcss );
			$topcss .= $style . "</style>";
			$out->addHeadItem( 'topcss', $topcss );
		} else {
			$out->addHeadItem( 'alternatedomain', HTML::inlineStyle( $style ) );
		}

		return true;
	}

	/*
	 * Allow Mediawiki to purge these alt domain URLs, which makes
	 * them cacheable at the varnish/fastly layer for Mediawiki.
	 */
	public static function onTitleSquidURLs($title, &$urls) {
		global $wgIsSecureSite;

		$rootDomain = self::getAlternateDomainForPage( $title->getArticleID() );
		if ( $rootDomain ) {
			$partialUrl = $title->getPartialUrl();
			$protos = array( 'https:', 'http:' );
			$newUrls = array();
			foreach ( $protos as $proto ) {
				$newUrls[] = "$proto//www.$rootDomain/$partialUrl";
				$newUrls[] = "$proto//m.$rootDomain/$partialUrl";
				$newUrls[] = "$proto//m.$rootDomain/$partialUrl?wh_an=1";
				$newUrls[] = "$proto//m.$rootDomain/$partialUrl?amp=1";
			}

			$urls = array_merge($urls, $newUrls);
		}

		return true;
	}

	public static function onWikihowTemplateAfterGetMobileUrl( &$mobileUrl, $title ) {
		global $wgIsSecureSite;
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		if ( !$title || !$title->getArticleID() ) {
			return true;
		}
		$proto = ($wgIsSecureSite ? 'https:' : 'http:');
		$partialUrl = $title->getPartialUrl();
		$rootDomain = self::getCurrentRootDomain();
		$mobileUrl = "$proto//m.$rootDomain/$partialUrl";
	}

	/*
	 * used to get related articles in domain and also articles for the home page
	 */
	private static function getTopArticlesInDomain( $limit, $offset = null ) {
		global $wgTitle, $wgLanguageCode;

		$pageId = $wgTitle->getArticleID();
		$pages = self::getAlternateDomainPagesForCurrentDomain();

		$dbr = wfGetDB( DB_REPLICA );
		$table = array( WH_DATABASE_NAME_EN.'.titus_copy' );
		$vars = array( 'ti_page_id' );
		$conds = array(
			'ti_page_id <> '.$pageId,
			'ti_page_id' => $pages,
			'ti_language_code' => $wgLanguageCode,
			'ti_robot_policy' => 'index,follow',
			'ti_num_photos > 0'
		);

		if ( SensitiveRelatedWikihows::isSensitiveRelatedRemovePage( $wgTitle ) ) {
			$srpTable = SensitiveRelatedWikihows::SENSITIVE_RELATED_PAGE_TABLE;
			$conds[] = "ti_page_id NOT IN (select srp_page_id from $srpTable)";
		}

		$orderBy = 'ti_30day_views DESC';
		$options = array( 'ORDER BY' => $orderBy, 'LIMIT' => $limit );
		if ( $offset ) {
			$options['OFFSET'] = $offset;
		}
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		$result = array();
		foreach ( $res as $row ) {
			$result[] = $row->ti_page_id;
		}
		return $result;
	}

	/*
	 * only show related wikhows from within the domain
	 * and on regular wikihow do now show alternate domain articles
	 */
	public static function onRelatedWikihowsAfterLoadRelatedArticles( $title, &$relatedArticles ) {
		if ( self::onAlternateDomain() ) {
			$limit = 14;
			$relatedArticles = array_flip( self::getTopArticlesInDomain( $limit ) );
			return true;
		} else {
			$allPages = self::getAllPages();
			foreach ( $relatedArticles as $pageId => $val ) {
				if ( isset( $allPages[$pageId] ) ) {
					unset( $relatedArticles[$pageId] );
				}
			}
		}
	}

	/*
	 * only show related wikhows from within the domain
	 * and on regular wikihow do now show alternate domain articles
	 */
	public static function onRelatedWikihowsBeforeLoadRelatedArticles( $title, &$relatedArticles ) {
		if ( self::onAlternateDomain() ) {
			return true;
		} else {
			$allPages = self::getAllPages();
			foreach ( $relatedArticles as $pageId => $val ) {
				if ( isset( $allPages[$pageId] ) ) {
					unset( $relatedArticles[$pageId] );
				}
			}
		}
	}

	/*
	 * on homepage we set the items from the domain articles
	 */
	public static function onWikihowHomepageAfterGetTopItems( &$items ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$count = count( $items );

		$topArticles = self::getTopArticlesInDomain( $count );

		$i = 1;
		$items = [];
		foreach ( $topArticles as $pageId ) {
			$item = new stdClass();
			$title = Title::newFromID( $pageId );
			$item->url = $title->getLocalURL();
			$item->text = $title->getText();

			// get big width/height
			$width = 1280;
			$thumbnailImage = ArticleMetaInfo::getTitleImageThumb( $title, $width );
			$item->imagePath = wfGetPad( $thumbnailImage->getUrl() );
			$item->itemNum = $i++;

			$items[] = $item;
		}
		return true;
	}

	/*
	 * on homepage we set the items from the domain articles
	 */
	public static function onWikihowHomepageFAContainerHtml( &$html, &$html2, &$html3 ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$html2 = "";
		$html3 = "";

		return true;
	}

	public static function getFATitles( $limit = 100 ): Array {
		if ( !self::onAlternateDomain() ) return [];

		$fas = [];

		$pages = array_reverse( self::getAlternateDomainPagesForCurrentDomain() );
		$pages = array_slice( $pages, 0, $limit );

		foreach ($pages as $page_id) {
			$fa_title = Title::newFromID( $page_id );

			if ($fa_title) {
				$fas[] = [ 'title' => $fa_title ];
			}
		}

		return $fas;
	}

	/*
	 * do not show follow widget on alternate domain
	 */
	public static function onWikihowTemplateShowFollowWidget( &$showFollowWidget ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$showFollowWidget = false;
	}

	/*
	 * do not show FA sidebar on alternate domain
	 */
	public static function onWikihowTemplateShowFeaturedArticlesSidebar( &$showFeaturedArticlesSidebar ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$showFeaturedArticlesSidebar = false;
	}

	/*
	 * do not show top links sidebar on alternate domain
	 */
	public static function onWikihowTemplateShowTopLinksSidebar( &$showTopLinksSidebar ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$showTopLinksSidebar = false;
	}

	/*
	 * handle case where we are on an alternate domain but are trying to execute the login page
	 * we must redirect to the main site.
	 * we have another redirect hook but it gets overriden by the login code
	 * since it tries to do it's own redirects so we have to hook in to the redirects
	 * a bit later for this case
	 */
	public static function onBeforePageRedirect( $out, &$redirect, &$code ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		if ( $out->getTitle() == SpecialPage::getTitleFor( 'Userlogin' ) ) {
			$queryString = $out->getRequest()->getRawQueryString();
			$url = self::getDestUrl( $out->getTitle()->getLocalURL(), "wikihow.com" );
			$url .= "?".$queryString;
			$redirect = "https://" . $url;
			$code = 301;
		}
	}

	// we do not ever redirect users to or from these pages for alternate domains
	private static function isOnNoRedirectPage( $title ) {
		if ( !$title ) {
			return false;
		}

		// only allow if it has ajax=true in query param
		if ( $title->inNamespace( NS_FILE ) ) {
			$request = RequestContext::getMain()->getRequest();
			$ajax = $request->getVal('ajax');
			if ( $ajax == 'true' ) {
				return true;
			}
			return false;
		}

		// there are certain special pages which no redirects
		$allowedSpecialPages = array(
			'RateItem',
			'RatingReason',
			'MethodHelpfulness',
			'QA',
			'UserCompletedImages',
			'LSearch',
			'Sitemap',
			'ArticleReviewers',
			'QABox',
			'BuildWikihowModal',
			'PageHelpfulness',
			'PageStats',
			'RCWidget'
		);

		$noRedirect = false;
		$titleText = $title->getText();
		foreach ( $allowedSpecialPages as $page ) {
			if ( $titleText == SpecialPage::getTitleFor( $page )->getText() ) {
				return true;
			}
		}

		$isMainPage = $title->inNamespace( NS_MAIN ) && $titleText == wfMessage('mainpage')->inContentLanguage()->text();
		if ( $isMainPage ) {
			return true;
		}

		// another exception
		if ( $title->inNamespace( NS_PROJECT ) && $titleText == 'Creative Commons' ) {
			return true;
		}
		if ( $title->inNamespace( NS_PROJECT ) && $titleText == 'Terms of Use' ) {
			return true;
		}
		if ( $title->inNamespace( NS_PROJECT ) ) {
			$domain = self::getCurrentRootDomain();
			$aboutPage = wfMessage( "footer_about_wh_{$domain}" )->text();
			if ( strpos( $aboutPage, $title->getDBKey() ) !== false ) {
				return true;
			}
		}

		if ( strstr( $titleText, 'StuInspector' ) ) {
			return true;
		}

		return false;
	}

	public static function isAltDomainLang() {
		global $wgLanguageCode;
		if ( $wgLanguageCode == 'en' ) {
			return true;
		} else {
			return false;
		}
	}

	/*
	 * check if we need to redirect to a different domain, given the page
	 * that we're on.
	 *
	 * NOTE: this method assumes that onAlternateDomain() returns true and
	 * that we're on an article page. If we change how this method is used,
	 * we should add this assertion, or handle more cases in this code.
	 */
	private static function redirectDomainForPage($pageId) {
		global $domainName, $wgIsDevServer;
		$altDomainforPage = self::getAlternateDomainForPage( $pageId );
		if ( !$altDomainforPage ) {
			// we're on an alt domain and page has no alt domain associated with it
			return true;
		} elseif ( strstr($domainName, $altDomainforPage) !== false ) {
			// page has alt domain associated with it, and user is visiting that domain
			return false;
		} elseif ($wgIsDevServer) {
			// page has alt domain associated with it, and on dev, user is visiting
			// a host with a stripped down version of that alt domain in it. For
			// example, user is visiting wikihowtech-p.wikidogs.com.
			$devAltDomain = self::devDomainToAltDomain($domainName);
			return $devAltDomain != $altDomainforPage;
		} else {
			// there is an alt domain for the page, and we're not on that domain
			return true;
		}
	}

	/*
	 * redirect anons to special domains if the page is on an alternate domain
	 * prohibit any query parameters on viewing an article on the alternate domain
	 * we redirect the page to the same url but with no query parameters at all
	 */
	public static function onBeforeInitialize( &$title, &$unused, &$output, &$user, $request, $wiki ) {
		// check if we are on a specific page that never redirects for the alt domains
		if ( self::isOnNoRedirectPage( $title ) ) {
			return true;
		}

		// never redirect in the case of a POST
		if ( $request->wasPosted() ) {
			return true;
		}

		// do not redirect if we are not on the english site
		if ( ! self::isAltDomainLang() ) {
			return true;
		}

		// in certain cases we want to 404 the current page.. check that
		if ( self::isOn404Page( $title->getArticleID() ) ) {
			return true;
		}

		$pageId = $title->getArticleID();
		if ( self::onAlternateDomain() ) {

			// Redirect to wikiHow if the page is not on the current alt domain
			$isCategoryPage = $title->inNamespace(NS_CATEGORY);
			$redirect = !$isCategoryPage && self::redirectDomainForPage($pageId);
			if ($redirect) {
				$url = self::getDestUrl( $request->getRequestURL(), "wikihow.com" );
				$output->redirect( $request->getProtocol() . "://" . $url, 301 );
				return true;
			}

			// if the user is on action=edit (ie clicked an edit link)
			// then send them to the wikihow domain and set a cookie
			// so they see the edited page when they finish
			if ( $request->getVal( 'action' ) == 'edit' ) {
				$response = RequestContext::getMain()->getRequest()->response();
				$response->setcookie( 'anonedit', '1' );
				$url = self::getDestUrl( $request->getRequestURL(), "wikihow.com" );
				$output->redirect( $request->getProtocol() . "://" . $url, 301 );
				return true;
			}

			// clear out other query params. if we found any
			// we must redirect to the same page but without those parameters
			$redirectToSelf = false;
			if (!$isCategoryPage) {
				$query = $request->getValues();
				foreach ( $query as $key => $value ) {
					// allow the title key since that is included in the request by default
					// also allow the oldid param since it is linked to by the verified revision
					if ( $key == "title" || $key == "oldid" ) {
						continue;
					}

					// allow useformat mobile key/val since it appears when visiting the mobile site
					if ( $key == "useformat" && $value == "mobile" ) {
						continue;
					}

					// allow bucket param for testing ads
					if ( $key == "bucket" ) {
						continue;
					}

					// allow printable view
					if ( $key == "printable" && $value == "yes" ) {
						continue;
					}

					// if on mobile then allow the amp param for amp mode
					if ( $key == "amp" && $value == "1") {
						continue;
					}

					// If on mobile allow the Android app request parameter
					if ( Misc::isMobileMode() &&
						class_exists('AndroidHelper') &&
						AndroidHelper::isAndroidRequest() &&
						$key == AndroidHelper::QUERY_STRING_PARAM &&
						$value == "1" ) {
						continue;
					}

					// if we found any extra params, then set a flag to redirect
					// and unset that query parameter
					$redirectToSelf = true;
					unset( $query[$key] );
				}
			}

			if ( $redirectToSelf == true ) {
				// unset the title param since it is included in the title full url already
				// and we don't want it appearing in the resulting url
				unset( $query['title'] );
				$url = wfAppendQuery( $title->getFullURL(), $query );
				$output->redirect( $url, 301 );
			}
		} else {
			// if we are on the regular wikihow domain check if we need to redirect
			// to the alternate domain

			// do not redirect if the user is logged in
			if ( $user && !$user->isAnon() ) {
				return true;
			}

			// if action=edit then do not redirect to alternate domain
			if ( $request->getVal( 'action' ) == 'edit' ) {
				return true;
			}

			// if there is a domain set for the current page, then redirect to it
			$altDomainforPage = self::getAlternateDomainForPage( $pageId );
			if ( $altDomainforPage ) {
				$url = self::getDestUrl( $request->getRequestURL(), $altDomainforPage );
				// always go to http version of the alternate domain page
				$output->redirect( "https://" . $url, 301 );
			}
		}
	}

	/*
	 * get the current root domain for example www.howyougetfit.com
	 * and m.howyougetfit.com would both return howyougetfit.com
	 * this is used to get the domain name we are currently on
	 * for use in mediawiki messages for example
	 */
	public static function getCurrentRootDomain() {
		global $domainName;

		$altDomain = self::devDomainToAltDomain($domainName);
		if ($altDomain) {
			return $altDomain;
		}

		$domain = explode ( '.', $domainName );
		if ( count( $domain ) < 2 ) {
			return "";
		} elseif ( count ( $domain ) == 2 ) {
			// do nothing
		} elseif ( count ( $domain ) == 3 ) {
			array_shift( $domain );
		} else {
			array_shift( $domain );
			array_shift( $domain );
		}
		$domain = implode( '.', $domain );
		return $domain;
	}

	/*
	 * override the lsearch to strip out domain name of url
	 */
	public static function onLSearchYahooAfterGetCacheKey( &$key ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		global $domainName;
		$key = $key . $domainName;
	}

	/*
	 * override the lsearch to strip out domain name of url
	 */
	public static function onLSearchAfterLocalizeUrl( &$result, $url ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$rootDomain = str_replace( '.', '\.', self::getCurrentRootDomain() );
		$result = preg_replace('@^https?://([^/]+\.)?'.$rootDomain.'/@', '', $url);
	}

	/*
	 * override the lsearch to alter the domain keyword search param
	 */
	public static function onLSearchBeforeYahooSearch( &$siteKeyword, &$surl ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$siteKeyword = "www.".self::getCurrentRootDomain();

		$surl = self::getCurrentRootDomain();
		if ( Misc::isMobileMode() ) {
			$surl = "mobile." . $surl;
		}
		$surl = "http://" . $surl;
	}

	/*
	 * override the lsearch to alter the domain keyword search param
	 */
	public static function onWikihowAdsAfterGetTypeTag( &$typeTag ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$typeTag = str_replace( '.', '_', self::getCurrentRootDomain() );
		if ( Misc::isMobileMode() ) {
			$typeTag = 'mobile_' . $typeTag;
		}
		$typeTag = '__alt__ddc_' . $typeTag;
	}

	/*
	 * override the lsearch to filter out any results that are not on
	 * the current alternate domain
	 */
	public static function onLSearchRegularSearch( &$results ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$currentDomain = array_flip( self::getAlternateDomainPagesForCurrentDomain() );
		$filteredResults = array();
		foreach ( $results as $result ) {
			$id = intval( $result['id'] );
			if ( isset( $currentDomain[$id ] ) ) {
				$filteredResults[] = $result;
			}
		}
		$results = $filteredResults;
	}

	/*
	 * override the randomizer to only look at pages in
	 * the current alternate domain
	 */
	public static function onRandomizerGetRandomTitle( &$title ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$pages = array_flip( self::getAlternateDomainPagesForCurrentDomain() );
		$pageId = array_rand( $pages );
		$title = Title::newFromID( $pageId );
	}

	/*
	 * override the sitemap page output to only show pages in
	 * the current alternate domain
	 */
	public static function onSitemapOutputHtml( &$html ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$titlesHtml = '';
		$pages = self::getAlternateDomainPagesForCurrentDomain();
		foreach ( $pages as $pageId ) {
			$title = Title::newFromID( $pageId );
			if (!$title || !$title->exists()) continue;
			$titlesHtml .= Html::rawElement( "li", array(), Linker::link( $title, $title->getText() ) );
		}
		$html = Html::rawElement( 'ul', ['class' => 'cat_list_ul'], $titlesHtml );
	}

	/*
	 * add an extra GA code for the alternate domains
	 */
	public static function onMiscGetExtraGoogleAnalyticsCodes( &$codes ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$altDomain = self::getCurrentRootDomain();

		if ( $altDomain == 'howyougetfit.com' ) {
			$codes['UA-2375655-16'] = 'howyougetfit';
		} elseif ( $altDomain == 'wikihow.tech' ) {
			$codes['UA-2375655-17'] = 'wikihowtech';
		} elseif ( $altDomain == 'wikihow.pet' ) {
			$codes['UA-2375655-20'] = 'wikihowpet';
		} elseif ( $altDomain == 'howyoulivelife.com' ) {
			$codes['UA-2375655-28'] = 'howyoulivelife';
		} elseif ( $altDomain == 'wikihow.mom' ) {
			$codes['UA-2375655-25'] = 'wikihowmom';
		} elseif ( $altDomain == 'wikihow.life' ) {
			$codes['UA-2375655-27'] = 'wikihowlife';
		} elseif ( $altDomain == 'wikihow.fitness' ) {
			$codes['UA-2375655-26'] = 'wikihowfitness';
		} elseif ( $altDomain == 'wikihow.health' ) {
			$codes['UA-2375655-31'] = 'wikihowhealth';
		} elseif ( $altDomain == 'wikihow-fun.com' ) {
			$codes['UA-2375655-32'] = 'wikihowfun';
		} elseif ( $altDomain == 'wikihow.legal' ) {
			$codes['UA-2375655-33'] = 'wikihowlegal';
		}
	}

	/*
	 * when viewing a category page remove any pages which are in an alternate domain
	 * if the user is logged in do not filter out the pages
	 */
	public static function onWikihowCategoryViewerQueryBeforeProcessTitle( $pageId ) {
		global $wgUser, $domainName;

		// if the user is logged in then do not filter out any pages
		if ( !$wgUser->isAnon() ) {
			return true;
		}

		$weAreOnAltDomain = self::onAlternateDomain();
		$articleAltDomain = self::getAlternateDomainForPage($pageId);
		if (!$weAreOnAltDomain && $articleAltDomain) {
			return false;
		}
		if ($weAreOnAltDomain && !strstr($domainName, $articleAltDomain)) {
			return false;
		}

		return true;
	}

	/*
	 * remove rss link from the template
	 */
	public static function onWikihowTemplateAfterGetRssLink( &$rssLink ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$rssLink = "";

		return true;
	}

	/*
	 * remove ios app link
	 */
	public static function onWikihowTemplateAddIOSAppIndexingLinkTag() {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		return false;
	}

	/*
	 * remove the android app link
	 */
	public static function onWikihowTemplateAddAndroidAppIndexingLinkTag() {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		return false;
	}



	// we can impement this in the future if we want to
	public static function onArticleMetaInfoShowTwitterMetaProperties() {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		return false;
	}

	// we can impement this in the future if we want to
	// but for now we just disable this
	public static function onArticleMetaInfoAddFacebookMetaProperties() {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		return false;
	}


	/*
	 * this hook will change the mobile href link
	 */
	public static function onWikihowTemplateAfterGetMobileLinkHref( &$mobileLinkHref, $partialUrl ) {
		global $domainName, $wgIsDevServer;

		if ( self::onAlternateDomain() ) {
			if ( ! $wgIsDevServer ) {
				$mobileLinkHref = 'https://' . str_replace( 'www', 'm', $domainName ) . $partialUrl;
			} else {
				$mobileLinkHref = 'https://' . str_replace( 'dev', 'dev.m', $domainName ) . $partialUrl;
			}
		}

		return true;
	}


	/*
	 * this hook will change the head links array
	 * to be correct for alternate domains
	 */
	public static function onOutputPageAfterGetHeadLinksArray( &$headLinks, $out ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$domain = self::getCurrentRootDomain();
		unset( $headLinks['apple-touch-icon'] );
		unset( $headLinks['favicon'] );
		unset( $headLinks['rsd'] );

		if ( $out->getTitle()->isMainPage() ) {
			$headLinks['meta-keywords'] = Html::element(
				'meta',
				['name' => 'keywords', 'content' => wfMessage( 'meta_keywords_' . $domain )]
			);
			$headLinks['meta-description'] = Html::element( 'meta', ['name' => 'description','content' => wfMessage( 'meta_description_' . $domain )] );
		}

		$hasAmpHtml = false;
		foreach ( $headLinks as $key => $val ) {
			if ( strstr( $val, 'atom' ) ) {
				unset( $headLinks[$key] );
			}
			if ( strstr( $val, 'canonical' ) ) {
				unset( $headLinks[$key] );
			}
			if ( strstr( $val, 'amphtml' ) ) {
				$hasAmpHtml = true;
				unset( $headLinks[$key] );
			}
		}

		// Add a canonical for everything but search urls
		$prefUrl = $out->getContext()->getTitle()->getPrefixedURL();
		if($prefUrl !== "Special:LSearch") {
			// Hack to fix the canonical URL on /Experts; since Special:ArticleReviewers has been redirected to /Experts
			if ( $prefUrl == "Special:ArticleReviewers" ) {
				$prefUrl = "Experts";
			}
			$canonicalUrl = 'https://www.' . $domain . '/' . $prefUrl;
			$headLinks[] = Html::element( 'link', array(
				'rel' => 'canonical',
				'href' => $canonicalUrl
			) );
		}

		//$serverUrl = "https://m.".$domain;
		if ( $hasAmpHtml ) {
			//$ampUrl = wfExpandUrl( $serverUrl . '/' . $out->getContext()->getTitle()->getPrefixedURL(), PROTO_CANONICAL );
			$ampUrl =  $canonicalUrl . "?amp=1";

			$headLinks[] = Html::element( "link", array( "rel" => "amphtml", "href" => $ampUrl ) );
		}
		return true;
	}

	/**
	 * Keep track of language links that point to alt pages, so we can use the
	 * correct alt domain in the INTL hreflang link instead of www.wikihow.com
	 */
	public static function onTranslationLinkAddLanguageLink( $translationLink ) {
		global $wgAltLanguageLinks;

		if (!Misc::isIntl()) {
			return true;
		}

		$wgAltLanguageLinks = $wgAltLanguageLinks ?? [];
		$pageId = (int) $translationLink->fromAID;
		$allPages = self::getAllPages();
		if ( isset( $allPages[$pageId] ) ) {
			$wgAltLanguageLinks[$translationLink->fromURL] = $allPages[$pageId];
		}

		return true;
	}

	/*
	 * this hook will override mediawiki messages
	 * so we can display custom message for the alternate domains
	 */
	public static function onMessageCacheGet( &$lckey ) {
		if ( self::onAlternateDomain() ) {
			$domain = self::getCurrentRootDomain();
			if ( $lckey == 'hp_tag' ) {
				$lckey = "hp_tag_{$domain}";
			}
			if ( $lckey == 'main_tag_mobile' ) {
				$lckey = "main_tag_mobile_{$domain}";
			}
			if ( $lckey == 'opensearch-desc' ) {
				$lckey = "opensearch-desc-{$domain}";
			}
			if ( $lckey == 'sp_section_name' ) {
				$lckey = "sp_section_name_alternate_domain";
			}
			if ( $lckey == 'pagetitle' ) {
				$lckey = "pagetitle_alternate_domain";
			}
			if ( $lckey == 'footer_about_wh' ) {
				$lckey = "footer_about_wh_{$domain}";
			}
			if ( $lckey == 'noarticletext' ) {
				$lckey = "noarticletext_altdomain";
			}
			if ( $lckey == 'pagepolicy_review_header' ) {
				$lckey = "pagepolicy_review_header_altdomain";
			}
			if ( $lckey == 'pagepolicy_review_message' ) {
				$lckey = "noarticletext_altdomain";
			}
			if ( $lckey == 'pagepolicy_search_header' ) {
				$lckey = "altdomain_blank";
			}
			if ( $lckey == 'pagepolicy_home_message' ) {
				$lckey = "altdomain_blank";
			}
		}

		if ( self::onNoBrandingDomain() ) {
			if ( $lckey == 'footer_terms' ) {
				$lckey = 'footer_terms_nobranding';
			}
			if ( $lckey == 'qa_generic_username' ) {
				$lckey = 'qausername_nobranding';
			}
		}

		return true;
	}

	/*
	 * This section is only for no branding domain
	 */

	/*
	 * this hook will remove certain notices which we do not show on the no branding domain
	 */
	public static function onWikihowTemplateAfterCreateNotices( &$siteNotice, &$cookieNotice, &$adblockNotice  ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}

		$siteNotice = "";
		$cookieNotice = "";
		$adblockNotice = "";

		return false;
	}

	/*
	 * do not show edit links on no branding domain
	 */
	public static function onRelatedWikihowsShowEditLink() {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		return false;
	}

	/*
	 * do not show qa box on alternate/no branding domains, as it links out to other articles
	 */
	public static function onQABoxAddToArticle() {
		return !self::onNoBrandingDomain() && !self::onAlternateDomain();
	}

	/*
	 * remove nav tabs on no branding domain
	 * alter the nav tabs on branded alt domain
	 */
	public static function onHeaderBuilderAfterGenNavTabs( &$navTabs ) {
		if ( self::onNoBrandingDomain() ) {
			$navTabs = array();
		} elseif ( self::onAlternateDomain() ) {
			$html = Linker::link( Title::makeTitle(NS_SPECIAL, 'Randomizer'), wfMessage('randompage')->text(), ['role' => 'menuitem'] );
			//$html .= Linker::link( Title::makeTitle(NS_SPECIAL, "CategoryListing"), wfMessage('navmenu_categories')->text(), ['role' => 'menuitem'] );
			$html = '<div class="menu" role="menu">'.$html.'</div>';
			$navTabs['nav_explore']['menu'] = $html;
			unset( $navTabs['nav_help'] );
			unset( $navTabs['nav_messages'] );
			unset( $navTabs['nav_profile'] );
			unset( $navTabs['nav_edit'] );
		} else {
			return true;
		}
	}

	/*
	* used to show the regular 404 page on alt domains
	*/
	public static function onPagePolicyShowCurrentTitle( $title, &$showCurrentTitle ) {
		// since we are only ever setting this to false under certain conditions
		// we can return early if it is already false
		if ( $showCurrentTitle == false) {
			return;
		}

		if ( !self::onAlternateDomain() ) {
			return;
		}

		if ( !$title->exists() ) {
			return;
		}

		if ( self::isOnNoRedirectPage( $title ) ) {
			return;
		}

		if ( self::isOn404Page( $title->getArticleID() ) ) {
			$showCurrentTitle = false;
		}
	}

	/*
	 * used to remove edit links.. could not find an easy way to do this in hooks
	 * and use to remove links to other wikihows that are on the other domain
	 * it might be possbile if we hook in to the parser to do these two
	 */
	public static function modifyDom() {
		global $wgTitle;
		if ( self::onAlternateDomain() ) {
			pq( '.editsection' )->remove();
			pq( '.edit-page' )->remove();
			// Replace links to other domains with their anchor text
			foreach(pq('a.interwiki_otherdomain') as $link) {
				$pqObject = pq($link);
				$pqObject->replaceWith($pqObject->text());
			}
			pq( '#sd_container' )->parents( '.section:first' )->remove();
			pq( '#sourcesandcitations .internal' )->remove();
			pq( '#references .internal' )->remove();
			pq( '.sp_fullbox' )->remove();

			//add the alt domain info as a class to intro
			pq("#intro")->addClass(self::getAlternateDomainClass(self::getAlternateDomainForCurrentPage()));
		} else {
			foreach(pq('a.interwiki_otherdomain') as $link) {
				$pqObject = pq($link);
				$pqObject->replaceWith($pqObject->text());
			}
		}
	}

	/*
	 * remove footer placeholder on no branding domains
	 */
	public static function onWikihowArticleBeforeProcessBody( $title ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$titleText = $title->getText();
		if ( $title->inNamespace( NS_PROJECT ) && $titleText == 'Creative Commons' ) {
			$domain = self::getCurrentRootDomain();
			$msg = wfMessage( "cc_{$domain}" )->parse();
			pq('div:first')->replaceWith( $msg );
			return true;
		}
		if ( $title->inNamespace( NS_PROJECT ) && $titleText == 'Terms of Use' ) {
			$domain = self::getCurrentRootDomain();
			$msg = wfMessage( "termsofuse_{$domain}" )->parse();
			pq('div:first')->replaceWith( $msg );
			return true;
		}
	}

	/*
	 * remove footer placeholder on no branding domains
	 */
	public static function onMobileTemplateBeforeRenderFooter( &$footerPlaceholder ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		$footerPlaceholder = "";
		return true;
	}

	/*
	 * remove top search from no branding
	 */
	public static function onWikihowTemplateAfterGetTopSearch( &$top_search ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		$top_search = '';

		return true;
	}

	/*
	 * change the section title for related wikihows
	 * to not reference wikihow. it is too complicated for now to simply
	 * override the mw message for relatedwikihows because that is used to
	 * select the name of the section class as well
	 */
	public static function onRelatedWikihowsBeforeGetSectionHtml( &$sectionTitle ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}

		$sectionTitle = wfMessage( 'relatedwikihows_nobranding' )->text();

		return true;
	}

	/*
	 * this hook will check any interwiki links on the page and give them a special
	 * css class if they are not part of the current alternate domains pages
	 * which is then used to hide those links
	 */
	public static function onGetLinkColours( $linkcolour_ids, &$colours ) {
		global $wgTitle;

		if ( !$wgTitle ) {
			return true;
		}
		$pageId = $wgTitle->getArticleID();
		if ( !$pageId ) {
			return true;
		}
		$altDomain = self::getAlternateDomainForPage($wgTitle->getArticleID());

		if (!$altDomain) {
			foreach ( $linkcolour_ids as $pageId => $colourKey ) {
				if ( self::getAlternateDomainForPage( $pageId ) ) {
					$colours[$colourKey] = 'interwiki_otherdomain';
				}
			}

			return true;
		}
		$pages = array_flip(self::getAlternateDomainPagesForDomain($altDomain));

		// add the terms of use page
		$pages[898454] = 1;

		foreach ( $linkcolour_ids as $pageId => $colourKey ) {
			if ( !isset( $pages[$pageId] ) && str_replace('-', ' ', $colourKey) != wfMessage('mainpage')->inContentLanguage()->text()) {
				$colours[$colourKey] = 'interwiki_otherdomain';
			}
		}
		return true;
	}

	/*
	 * set output on 404 page for special pages
	 */
	public static function onSpecialPageBeforeExecute( $page, $subPage ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		if ( self::isOnNoRedirectPage( $page->getTitle() ) ) {
			return true;
		}
		echo "page not found";
		die();
	}

	/*
	 * set output on 404 page
	 */
	public static function onOutputPageBeforeHTML( &$out, &$text ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$title = $out->getTitle();
		$titleText = $title->getText();
		if ( $title->inNamespace( NS_PROJECT ) && $titleText == 'Creative Commons' ) {
			return true;
		}
		if ( $title->inNamespace( NS_PROJECT ) && $titleText == 'Terms of Use' ) {
			return true;
		}
		if ( !self::onNoBrandingDomain() ) {
			if ( !$out->getTitle()->inNamespace(NS_MAIN) ) {
				return true;
			}
		}
	}

	/*
	 * if we are on a no branding site, then we 404
	 * if the page we are trying to visit is not part of the same domain
	 */
	private static function isOn404Page( $pageId ) {
		global $wgTitle;
		if ( !self::onAlternateDomain() ) {
			return false;
		}

		// if on regular wikihow branded alternate domain
		if ( !self::onNoBrandingDomain() ) {
			// if the title is not in the main namespace then do not 404 the page
			if ( !$wgTitle->inNamespace(NS_MAIN) ) {
				return false;
			}
		}

		$currentPages = array_flip( self::getAlternateDomainPagesForCurrentDomain() );
		if ( !isset( $currentPages[$pageId] ) ) {
			return true;
		}
		return false;
	}

	/*
	 * fix the schema markup to not reference wikihow
	 */
	public static function onSchemaMarkupAfterGetData( &$data ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		// TODO fix both of these in the future
		unset( $data['publisher'] );
		unset( $data['image'] );


		return true;
	}

	/*
	 *  remove the logo link
	 */
	public static function onWikihowTemplateAfterGetLogoLink( &$logoLink ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		$logoLink = "";

		return false;
	}

	/*
	 * disable the footers and search since they say "wikihow to"
	 */
	public static function onMinvervaTemplateBeforeRender( &$data ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		$data['disableSearchAndFooter'] = true;
		$data['disableFooter'] = true;
		return true;
	}

	/*
	 * remove share buttons
	 */
	public static function onMinervaTemplateWikihowBeforeRenderShareButtons( &$renderShareButtons ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		$renderShareButtons = false;
		return true;
	}

	public static function onWikihowTemplateBeforeCreateLogoImage( &$logoPath, &$logoClass ) {
		$altDomain = self::getCurrentRootDomain();

		if ( $altDomain == 'wikihow.tech' ) {
			$logoPath = '/skins/owl/images/wikihow_logo_tech_4.png';
			$logoClass[] = 'tech_logo';
		} elseif ( $altDomain == 'wikihow.pet' ) {
			$logoPath = '/skins/owl/images/wikihow_logo_pet_3.png';
			$logoClass[] = 'pet_logo';
		} elseif ( $altDomain == 'wikihow.mom' ) {
			$logoPath = '/skins/owl/images/wikihow_logo_mom.png';
			$logoClass[] = 'mom_logo';
		} elseif ( $altDomain == 'wikihow.fitness' ) {
			$logoPath = '/skins/owl/images/wikihow_logo_fitness.png';
			$logoClass[] = 'fitness_logo';
		} elseif ( $altDomain == 'wikihow.legal' ) {
			$logoPath = '/skins/owl/images/wikihow_logo_legal.png';
			$logoClass[] = 'legal_logo';
		}
	}

	public static function onMinervaTemplateWikihowBeforeCreateHeaderLogo( &$headerClass ) {
		$altDomain = self::getCurrentRootDomain();

		if ( $altDomain == 'wikihow.tech' ) {
			if ( $headerClass ) {
				$headerClass = $headerClass . ' tech_logo';
			} else {
				$headerClass = 'tech_logo';
			}
		} elseif ( $altDomain == 'wikihow.pet' ) {
			if ( $headerClass ) {
				$headerClass = $headerClass . ' pet_logo';
			} else {
				$headerClass = 'pet_logo';
			}
		} elseif ( $altDomain == 'wikihow.mom' ) {
			if ( $headerClass ) {
				$headerClass = $headerClass . ' mom_logo';
			} else {
				$headerClass = 'mom_logo';
			}
		} elseif ( $altDomain == 'wikihow.fitness' ) {
			if ( $headerClass ) {
				$headerClass = $headerClass . ' fitness_logo';
			} else {
				$headerClass = 'fitness_logo';
			}
		} elseif ( $altDomain == 'wikihow.legal' ) {
			if ( $headerClass ) {
				$headerClass = $headerClass . ' legal_logo';
			} else {
				$headerClass = 'legal_logo';
			}
		}
	}

	/*
	 * a hook that runs very early to set wgServer to the proper value for alt domains
	 * so that the mobile hooks will work. it also sets useformat=mobile if we are on m. domain
	 */
	public static function onSetupAfterCache() {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		global $wgRequest, $wgServer, $wgIsDevServer;
		$httpHost = (string)$wgRequest->getHeader('HOST');
		$domains = self::getAlternateDomains();
		foreach ( $domains as $domain ) {
			$mobileDomain = 'm.' . $domain;
			$desktopDomain = 'www.' . $domain;

			if ( $wgIsDevServer ) {
				$mobileDomain = 'dev.m.' . $domain;
				$desktopDomain = 'dev.' . $domain;
			}

			// allow visitors to mobile or desktop vesion of the alternate domain page
			if ( $httpHost == $desktopDomain || $httpHost == $mobileDomain ) {
				$wgServer = 'https://' . $httpHost;
			}

			// if the visitor hits the alternate domain without a prefix (eg http://howyougetfit.com) then set the server to the desktop domain
			if ( $httpHost == $domain ) {
				$wgServer = 'https://' . $desktopDomain;
			}

			// it would normally be done in onSetupAfterCacheSetMobile in PageHooks
			// this needs to be repeated here because this hook runs after that one
			if ( $httpHost == $mobileDomain ) {
				$wgRequest->setVal('useformat', 'mobile');
			}
		}
		return true;
	}

	/*
	 * a hook alters the google amp ad slot data
	 */
	public static function onGoogleAmpAfterGetSlotData( &$slotData ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}

		$firstAd = 2905689278;
		$regularAd = 1759325775;

		$slotData['en'] = array(
			1 => $firstAd,
			2 => $regularAd,
			3 => $regularAd,
			4 => $regularAd,
			5 => $regularAd,
			6 => $regularAd,
			7 => $regularAd,
			8 => $regularAd,
			9 => $regularAd,
		);
	}

	/*
	 * a hook alters the main page title
	 */
	public static function onWikihowSkinHelperAfterGetMainPageHtmlTitle( &$htmlTitle ) {
		if ( !self::onNoBrandingDomain() ) {
			return true;
		}
		$domain = self::getCurrentRootDomain();
		$htmlTitle = wfMessage("main_title_{$domain}")->text();
	}

	/*
	 * a hook alters the links in the sidebar on mobile
	 */
	public static function onWikihowMobileSkinAfterPrepareDiscoveryTools( &$items ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		unset( $items['categories'] );
		unset( $items['morethings'] );
		unset( $items['spellchecker'] );
		unset( $items['techfeedback'] );
		unset( $items['sortquestions'] );
		unset( $items['notifications'] );
		unset( $items['addtip'] );
		unset( $items['header2'] );
		unset( $items['header3'] );
		unset( $items['quizyourself'] );
	}

	/*
	 * another hook alters the links in the sidebar on mobile
	 */
	public static function onWikihowMobileSkinAfterPreparePersonalTools( &$items ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		unset( $items['auth'] );
	}

	/*
	 * remove the resource modules on mobile which create userlogin links on the page
	 */
	public static function onResourceLoaderRegisterModules( &$resourceLoader ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		global $wgResourceModules;
		unset( $wgResourceModules[ 'mobile.newusers' ] );
		unset( $wgResourceModules[ 'mobile.editor' ] );
		if ( isset($wgResourceModules['mobile.stable']['scripts']) ) {
			$key = array_search( 'javascripts/modules/mf-watchstar.js', $wgResourceModules['mobile.stable']['scripts'] );
			if ( $key !== false) {
				unset( $wgResourceModules['mobile.stable']['scripts'][$key] );
			}
		}
	}

	/*
	 * disable showing the category listing link until it is ready
	 */
	public static function onHeaderBuilderGetCategoryLinksShowCategoryListing( &$showCategoryListing ) {
		if ( !self::onAlternateDomain() ) {
			return true;
		}
		$showCategoryListing = false;
	}

	private static function termsOfUseHTML(): string {
		$msg = wfMessage('termsofuse_altdomain')->parse();
		return HTML::rawElement('div', ['class' => 'section_text'], $msg);
	}

	/*
	 * remove the links from the top tabs (like article and edit links)
	 */
	public static function onShowArticleTabs( &$showTabs ) {
		if (self::onAlternateDomain()) $showTabs = false;
	}

	/*
	 * remove the breadcrumbs
	 */
	public static function onShowBreadCrumbs( &$showCrumbs ) {
		if (self::onAlternateDomain()) $showCrumbs = false;
	}
}

