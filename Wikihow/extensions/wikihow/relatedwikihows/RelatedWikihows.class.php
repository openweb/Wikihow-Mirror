<?php
$wgHooks['PageContentSaveComplete'][] = array('RelatedWikihows::clearArticleMemc');
class RelatedWikihow {
	var $mThumbUrl = '';
	var $mText = '';
	var $mUrl = '';

	public function createSidebarHtml() {
		return $this->createHtml( RelatedWikihows::SIDEBAR_IMG_WIDTH, RelatedWikihows::SIDEBAR_IMG_HEIGHT, true );
	}

	public function createDesktopHtml() {
		return $this->createHtml( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT, false );
	}

	public function createMobileHtml() {
		return $this->createHtml( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT );
	}

	public function createFastRenderHtml() {
		return $this->createHtmlFastRender( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT );
	}

	public function createAmpHtml() {
		return $this->createHtml( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT, false, true );
	}

	// creates the html for the related wikihows image, including fallback noscript tag and the scrollloading js snippet for lazy loading
	// will use a video instead of an image if the article has a video src
	private function createRelatedImgHtml( $width, $height, $isSidebar, $ampMode, $afterImgElement = '' ) {
		$imgSrc = '';
		$file = RepoGroup::singleton()->findFile( $this->mTitleImageName );
		if ( $file ) {
			$thumb = $file->getThumbnail( $width, $height, true, true ) ;
			$imgSrc = $thumb->getUrl();
		}
		$videoSrc = '';
		if ( !$isSidebar ) {
			$videoSrc = $this->mVideoUrl;
		}

		$imgSrc = wfGetPad( $imgSrc );
		$text =  $this->mText;
		$url =  $this->mUrl;
		$id = HtmlElementIdMap::getElementId( $imgSrc );

		$imgAttributes = [
			'id' => $id,
			'class' => 'scrolldefer content-fill',
			'src' => $imgSrc,
			'alt' => $text,
			'width' => $width,
			'height' => $height
		];

		if ( $isSidebar ) {
			unset( $imgAttributes['class'] );
		}

		// create the fallback noscript img tag
		$noscript = Html::openElement('noscript')
			. Html::element('img', ['src' => $imgAttributes['src']])
			. Html::closeElement('noscript');

		$imgAttributes['data-src'] = $imgAttributes['src'];
		unset( $imgAttributes['src'] );
		$img = Html::rawElement( 'img', $imgAttributes );

		// now create the video if we have it
		$videoElement = '';
		if ( $videoSrc ) {
			$videoAttributes = [
				'id' => $id,
				'class' => 'scrolldefer content-fill',
				'data-src' => $videoSrc,
				'alt' => $this->mText,
				'width' => $width,
				'height' => $height,
				'playsinline' => '',
				'webkit-playsinline' => '',
				'muted' => '',
				'data-poster' => $imgSrc,
				'loop' => '',
				//'autoplay' => ''
			];
			$videoElement = Html::rawElement( 'video', $videoAttributes );
		}

		if ( $videoElement ) {
			$img = $videoElement;
		}

		$img = $img . $afterImgElement;
		if ( !$isSidebar ) {
			$img = Html::rawElement( 'div', [ 'class' => 'content-spacer' ], $img );
		}

		$script = "WH.shared.addScrollLoadItem('$id')";
		$script = Html::inlineScript( $script );
		$img .= $script . $noscript;

		if ( $ampMode ) {
			$imgAttributes = [
				'src' => $imgSrc,
				'alt' => $text,
				'width' => $width,
				'height' => $height,
				'layout' => 'responsive'
			];
			$img = Html::rawElement( 'amp-img', $imgAttributes );
		}
		return $img;
	}

	// creates html that has fewer dom elements than the regular version
	private function createHtmlFastRender( $width, $height, $isSidebar = false, $ampMode = false ) {
		// the text to show for each related wikihow
		$howToPrefix = wfMessage( 'howto_prefix' )->showIfExists();
		$howToPrefix = Html::element( 'div', ['class' => 'related-wh-howto'], $howToPrefix );
		$howToText = $howToPrefix . $this->mText . wfMessage('howto_suffix')->showIfExists();
		$titleText = Html::rawElement( "span", [ 'class' => 'related-wh-title' ], $howToText );

		$img = $this->createRelatedImgHtml($width, $height, $isSidebar, $ampMode, $titleText );

		$linkAttributes = [
			'class' => 'related-wh',
			'href' => $this->mUrl
		];
		$link = Html::rawElement( "a", $linkAttributes, $img );

		return $link;
	}


	private function createHtml( $width, $height, $isSidebar = false, $ampMode = false ) {
		$img = $this->createRelatedImgHtml( $width, $height, $isSidebar, $ampMode  );

		$linkAttributes = [
			'class' => 'related-image-link',
			'href' => $this->mUrl
		];
		$link = Html::rawElement( "a", $linkAttributes, $img );

		// this is the wrapper div around the link
		$imageAttributes = [
			'class' => 'related-image',
		];
		$image = Html::rawElement( "div", $imageAttributes, $link );


		// the text to show for each related wikihow
		$msg = wfMessage('howto_prefix');
		$howToPrefix = $msg->exists() ? ('<p>' . $msg->text() . '</p>') : '';
		$howToText = $howToPrefix . $this->mText . wfMessage('howto_suffix')->showIfExists();
		$titleText = Html::rawElement( "span", [ 'class' => 'related-title-text' ], $howToText );
		$titleAttributes = [
			'class' => 'related-title',
			'href' => $this->mUrl
		];
		$titleLink = Html::rawElement( "a", $titleAttributes, $titleText );

		// we wrap it all in a div for styling purposes
		$wrapperAttributes = [
			'class' => 'related-article'
		];
		$wrapper = Html::rawElement( "div", $wrapperAttributes, $image . $titleLink);

		return $wrapper;
	}
}

class RelatedWikihows {

	const RELATED_IMG_WIDTH = 342;
	const RELATED_IMG_HEIGHT = 184;
	const MOBILE_IMG_WIDTH = 360;
	const MOBILE_IMG_HEIGHT = 231;
	const SIDEBAR_IMG_WIDTH = 127;
	const SIDEBAR_IMG_HEIGHT = 140;
	const SIDEBAR_LARGER_IMG_WIDTH = 290;
	const SIDEBAR_LARGER_IMG_HEIGHT = 156;
	const MIN_TO_SHOW_DESKTOP = 14;
	const QUERY_STRING_PARAM = "newrelateds";
	const MEMCACHED_KEY = "relarticles_data1";

	var $mShowEdit = null;
	var $mEditLink = '';
	var $mTitle = null;
	var $mShowSection = null;
	var $mMobile = false;
	var $mAmpMode = false;
	var $mUsingPhpQuery = false;
	var $mShowOtherWikihowsTitle = false;
	var $mIdName = "relatedwikihows";
	var $mAd = null;

	static $relatedWikihowsInstance = null;

	public static function getRelatedWikihows() {
		return self::$relatedWikihowsInstance;
	}

	public function __construct( $context, $user, $relatedSection = '' ) {
		$title = $context->getTitle();
		$this->mTitle = $title;
		$this->mUser = $user;
		$this->mShowEdit = $title->quickUserCan( 'edit', $user );
		$this->mShowSection = $title && $title->inNamespace( NS_MAIN ) && $title->exists() && !$title->isRedirect() && PagePolicy::showCurrentTitle( $context );
		$this->mMobile = Misc::isMobileMode();
		$this->mIdName = self::getSectionName();

		//if the $relatedSection is passed in,
		//we can safely assume that the php query object is set
		$this->mUsingPhpQuery = $relatedSection != '';

		if ($this->mUsingPhpQuery) {
			$this->loadRelatedArticles( pq($relatedSection)->find( '#'.$this->mIdName ) );
			if ( $this->mMobile ) {
				$this->mEditLink = trim( pq( $relatedSection )->find( '.edit-page' ) );
			} else {
				$this->mEditLink = trim( pq( $relatedSection )->find( '.editsection' ) );
			}
		}
		else {
			$this->loadRelatedArticles();
			$this->mEditLink = '';
		}

		$this->mAmpMode = GoogleAmp::isAmpMode( $context->getOutput() );
		self::$relatedWikihowsInstance = $this;
	}

	/*
	 * set html on an ad to appear in related wikihows
	 */
	public function setAdHtml( $adHtml ) {
		$this->mAd = $adHtml;
	}
	public static function forceShowNewRelated( $out ) {
		$showNew = $out->getRequest()->getVal( self::QUERY_STRING_PARAM ) == 1;
		return $showNew;
	}

	public static function forceShowOldRelated( $out ) {
		$showOld = $out->getRequest()->getVal( self::QUERY_STRING_PARAM ) === '0';
		return $showOld;
	}

	/*
	 * get list of related articles from a title given a category
	 * @param Title $title the title of the page to act on
	 * @param string $cat the category to look in
	 *
	 * @return array result is assoc array with pageids as they key
	 */
	private static function getRelatedArticlesForTitleAndCategory( $title, $cat ) {
		global $wgLanguageCode;

		if ( !$title ) {
			return array();
		}

		if ( !$cat ) {
			return array();
		}

		$cat = $cat->getDBKey();
		$result['category'] = $cat;

		// Populate related articles box with other articles in the category,
		// displaying the featured articles first
		$result = [];

		$dbr = wfGetDB( DB_REPLICA );

		$pageId = $title->getArticleID();
		$table = array( WH_DATABASE_NAME_EN.'.titus_copy' );
		$vars = array( 'ti_page_id' );

		$conds = array(
			'ti_page_id <> '.$pageId,
			'ti_language_code' => $wgLanguageCode,
			'ti_robot_policy' => 'index,follow',
			'ti_num_photos > 0'
		);

		$table[] = 'categorylinks';
		$conds[] = 'ti_page_id = cl_from';
		$conds['cl_to'] = $cat;

		if ( SensitiveRelatedWikihows::isSensitiveRelatedRemovePage( $title ) ) {
			$srpTable = SensitiveRelatedWikihows::SENSITIVE_RELATED_PAGE_TABLE;
			$conds[] = "ti_page_id NOT IN (select srp_page_id from $srpTable)";
		}

		$orderBy = 'ti_30day_views DESC';
		$limit = self::MIN_TO_SHOW_DESKTOP;
		$options = array( 'ORDER BY' => $orderBy, 'LIMIT' => $limit );

		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		if ( !$res ) {
			return $result;
		}

		foreach ( $res as $row ) {
			$targetId = $row->ti_page_id;
			$result[$targetId] = true;
		}

		return $result;
	}


	/*
	 * get list of categories we can search in for related wikihows
	 * filters out categories to ignore
	 * @param Title $title the title of the page to act on
	 *
	 * @return first title which fits the criteria or null
	 */
	private static function getCategoryForTitle( $title ) {
		if ( !$title ) {
			return null;
		}

		$categories = $title->getParentCategories();

		if ( !is_array( $categories ) || empty( $categories ) ) {
			return null;
		}

		// first get an associative array of categories to ignore
		$categoriesToIgnore = wfMessage( 'categories_to_ignore' )->inContentLanguage()->text();
		$categoriesToIgnore = explode( "\n", $categoriesToIgnore );
		$tempCategories = array();
		foreach ( $categoriesToIgnore as $catToIgnore ) {
			$parts = explode( ":", $catToIgnore );
			$tempCategories[] = end( $parts );
		}
		$categoriesToIgnore = array_flip( $tempCategories );

		$keys = array_keys( $categories );
		for ( $i = 0; $i < sizeof( $keys ); $i++ ) {
			$t = Title::newFromText( $keys[$i] );
			$partial = $t->getPartialURL();
			if ( isset( $categoriesToIgnore[urldecode( $partial )] ) || isset( $categoriesToIgnore[$partial] ) ) {
				continue;
			} else {
				//$result[] = $t->getDBKey();
				return $t;
			}
		}

		return null;
	}

	/*
	 * get list of related articles and their category given a title
	 * @param Title $title the title of the page to act on
	 *
	 * @return array contains an associative array with "category" as the first key
	 * which is the category of the title passed in, and "articles" as the other key
	 * which is an array whose keys are pageIds of relted articles in that category
	 */
	public static function getRelatedArticlesByCat( $title ) {
		global $wgMemc;

		$cachekey = wfMemcKey( self::MEMCACHED_KEY, $title->getArticleID() );
		$val = $wgMemc->get( $cachekey );
		if ( $val ) {
			return $val;
		}

		$category = self::getCategoryForTitle( $title );
		if ( empty( $category ) ) {
			// return a list from chris
			$result['articles'] = self::getDefaultRelatedWikihows();
		} else {
			// Populate related articles box with other articles in the category,
			$data = self::getRelatedArticlesForTitleAndCategory( $title, $category );

			$result = array();
			$result['category'] = $category;
			$result['articles'] = $data;
		}

		$wgMemc->set( $cachekey, $result );
		return $result;
	}

	/*
	 * a list of 14 related wikihows to show if we have no others
	 */
	private static function getDefaultRelatedWikihows() {
		global $wgLanguageCode;
		switch($wgLanguageCode) {
			case "en":
				return array_flip( [ 57203, 4157156, 14093, 5207, 1304771, 3450, 22372, 5014, 221266, 1622, 2959, 30513, 6257, 384626 ] );
			case "es":
				return array_flip( [32541, 98841, 3142, 12655, 77500, 23654, 23341, 14826, 13254, 18191, 12852, 1436, 20560, 12511] );
			case "de":
				return array_flip( [4198, 32518, 12406, 4673, 27225, 7799, 11686, 5815, 12586, 16561, 8326, 4876, 29492, 5084] );
			case "fr":
				return array_flip( [8785, 22317, 7257, 14485, 23875, 6418, 8578, 11787, 15121, 12279, 9117, 8902, 10731, 13093] );
			case "it":
				return array_flip( [5488, 35554, 11372, 13190, 23176, 6435, 10724, 8862, 11514, 15522, 10642, 10964, 13743, 15655] );
			case "pt":
				return array_flip( [11654, 66115, 17629, 19055, 28442, 6307, 4793, 16089, 46904, 22102, 12356, 4699, 22555, 22474] );
			case "hi":
				return array_flip( [2118790, 2115608, 2115433, 2119566, 2117838, 2115081, 2120215, 2120563, 2126158, 2115389, 2119459] );
			case "nl":
				return array_flip( [3526, 10973, 7119, 3753, 11836, 3561, 3586, 23329, 3840, 8867, 27734, 4479, 13008, 11375] );
			case "ru":
				return array_flip( [2139451, 2154339, 2139452, 2346773, 2152435, 2139442, 2140801, 2141004, 2140322, 2140260, 2155885, 2139582, 2144050, 2140571] );
			case "cs":
				return array_flip( [510, 7327, 515, 915, 4418, 1280, 577, 743, 920, 6899, 2649] );
			case "id":
				return array_flip( [2132943, 2140201, 2142509, 2133064, 2139587, 2132986, 2135809, 2133323, 2133129, 2139186, 2142251, 2133537, 2134180] );
			case "ja":
				return array_flip( [4503555, 4502775, 4502799, 4506169, 4503792, 4502187, 4506605, 4501557, 4502489, 4502211, 4503144] );
			case "ar":
				return array_flip( [2712, 4739, 2320, 10093, 5449, 2801, 4965, 3295, 14126, 19423, 2857, 8297, 6611] );
			case "th":
				return array_flip( [5201, 1367, 1100, 7455, 5481, 1665, 1154, 3393, 2311] );
			case "ko":
				return array_flip( [4247, 7347, 3844, 4207, 697, 2991, 5671, 567] );
			case "vi":
				return array_flip( [8173, 1578, 2139, 10746, 4459, 919, 8654, 3037, 817, 8615, 3046, 12230] );
			case "tr":
				return array_flip( [977, 946, 1758, 1064, 842, 4970, 3780, 2196] );
			default:
				return [];
		}
	}

	// gets the related wikihow titles from wikitext
	private static function getRelatedArticlesFromWikitext( $relatedSection ) {
		global $wgTitle;
		$relatedArticles = array();

		$isSensitiveRelatedRemovePage = SensitiveRelatedWikihows::isSensitiveRelatedRemovePage( $wgTitle );
		//first lets check to make sure all the related are indexed
		foreach ( pq( "li a", $relatedSection ) as $related ) {
			$titleText = pq( $related )->attr( "title" );
			$title = Title::newFromText( $titleText );
			if ( !$title ) {
				continue;
			}
			if ( !$title->exists() ) {
				continue;
			}
			$id = $title->getArticleID();
			if ( !self::isIndexed( $id ) ) {
				continue;
			}
			if ( $isSensitiveRelatedRemovePage && SensitiveRelatedWikihows::isSensitiveRelatedPage( $id ) ) {
				continue;
			}
			$relatedArticles[$id] = true;
		}
		return $relatedArticles;
	}

	// takes in an array of titles
	// return an array containing just the info needed to create the related section
	// also gets the image thumbnails
	public static function makeRelatedArticlesData( $relatedArticles ) {
		// now that we have the list of titles, we can make a more compact array of related data
		// that is easily cachable
		$width = self::RELATED_IMG_WIDTH;
		$height = self::RELATED_IMG_HEIGHT;
		$sideWidth = self::SIDEBAR_IMG_WIDTH;
		$sideHeight = self::SIDEBAR_IMG_HEIGHT;
		$related = array();

		foreach ( $relatedArticles as $id => $val ) {
			$title = Title::newFromID( $id );
			if ( !$title || !$title->exists() ) {
				continue;
			}
			// uncomment this to get the article's representative gif and video
			//$gifUrl = ArticleMetaInfo::getGif( $title );
			$videoUrl = ArticleMetaInfo::getVideoSrc( $title );
			$titleImageName = ArticleMetaInfo::getTitleImageName( $title );

			if ( !$titleImageName ) {
				continue;
			}

			$item = new RelatedWikihow();
			//$item->mGifUrl = $gifUrl;
			$item->mVideoUrl = $videoUrl;
			$item->mTitleImageName = $titleImageName;
			$item->mText = $title->getText();
			$item->mUrl = $title->getLocalURL();

			$related[] = $item;
		}
		return $related;
	}


	private static function isIndexed( $pageId ) {
		$dbr = wfGetDB( DB_REPLICA );
		$count = $dbr->selectField(
			'index_info',
			'count(*)',
			array( 'ii_page' => $pageId, 'ii_policy in (1, 4)' ),
			__METHOD__ );

		return $count > 0;
	}

	private function getRelatedArticles( $title ) {
		return $this->mRelatedWikihows;
	}

	private function loadRelatedArticles( $relatedSection = '' ) {
		$relatedArticles = [];
		$title = $this->mTitle;
		// get the related articles in the wikitext
		if ($this->mUsingPhpQuery) {
			$relatedArticles = self::getRelatedArticlesFromWikitext( $relatedSection );
		}

		Hooks::run( 'RelatedWikihowsBeforeLoadRelatedArticles', array( $title, &$relatedArticles ) );

		// minimum number to always show
		$minNumber = self::MIN_TO_SHOW_DESKTOP;
		$count = count( $relatedArticles );
		if ( $count < $minNumber ) {
			// the array keys are the page ids, and the + operator here
			// prevent us having duplicate keys when combing arrays
			$relatedByCat = $this->getRelatedArticlesByCat( $title );
			if ( isset( $relatedByCat['category'] ) && $relatedByCat['category'] === '' ) {
				$this->mShowOtherWikihowsTitle = true;
			}
			if ( isset( $relatedByCat['articles'] ) ) {
				$relatedArticlesByCategory = $relatedByCat['articles'];
			} else {
				$relatedArticlesByCategory = $relatedByCat;
			}

			$relatedArticles = $relatedArticles + $relatedArticlesByCategory;

			// limit the results of the wikitext and by-cat
			$relatedArticles = array_slice( $relatedArticles, 0, self::MIN_TO_SHOW_DESKTOP, true );
		}

		Hooks::run( 'RelatedWikihowsAfterLoadRelatedArticles', array( $title, &$relatedArticles ) );

		// pull out the needed data from the related articles and create thumbnail urls
		$this->mRelatedWikihows = self::makeRelatedArticlesData( $relatedArticles );

		return $this->mRelatedWikihows;
	}

	// mostly copied from wikihow ArticleHooks onDoEditSectionLink
	// but doesn't have a secton number.
	// for mobile version we could use SkinMinerva.php doEditSedctionLink
	// however that requires a section number..so for now leave it blank
	private function createEditLink() {
		if ( $this->mMobile || $this->mUser->isAnon() ) {
			return "";
		}

		$query = array();
		$query['action'] = "edit";

		$tooltip = wfMessage('relatedwikihows');
		$customAttribs = array(
			'class' => 'mw-editsection',
			'onclick' => "gatTrack(gatUser,'Edit','Edit_section');",
			'tabindex' => '-1',
			'title' => wfMessage('editsectionhint')->rawParams( htmlspecialchars($tooltip) )->escaped(),
			'aria-label' => wfMessage('aria_edit_section')->rawParams( htmlspecialchars($tooltip) )->showIfExists(),
		);

		$result = Linker::link( $this->mTitle, wfMessage('editsection')->text(), $customAttribs, $query, "known");
		return $result;
	}

	// the main function to be called to get the related articles section
	private function getSectionHtml() {
		$sectionTitle = "Related wikiHows";
		if ( $this->mShowOtherWikihowsTitle ) {
			$sectionTitle = wfMessage( 'otherwikihows' );
		} else {
			$sectionTitle = wfMessage( 'relatedwikihows' )->text();
		}

		Hooks::run( 'RelatedWikihowsBeforeGetSectionHtml', array( &$sectionTitle ) );

		$span = Html::element( "span", array( 'class'=>'mw-headline', 'id'=>'Related_wikiHows' ), $sectionTitle );
		$editLink = $this->mEditLink;
		if ( !$editLink && $this->mShowEdit && Hooks::run( 'RelatedWikihowsShowEditLink', array() ) ) {
			$editLink = $this->createEditLink();
		}
		$heading = Html::rawElement( "h2", array(), $editLink . $span );
		$editlink_text = wfMessage( 'editarticle' )->text();

		$relatedWikihows = $this->mRelatedWikihows;
		// if odd number
		if ( count( $relatedWikihows ) % 2 == 1 ) {
			array_pop( $relatedWikihows );
		}

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$insertAd = false;
		if ( $this->mAd && count( $relatedWikihows ) > 1 && !$this->mMobile && !$this->mAmpMode ) {
			$insertAd = true;
		}

		if ( $insertAd ) {
			array_pop( $relatedWikihows );
		}

		$fastRender = false;
		if ( Misc::isFastRenderTest() ) {
			$fastRender = true;
		}

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			if ( $this->mAmpMode ) {
				$thumbs .= $relatedWikihow->createAmpHtml();
			} elseif ( $fastRender ) {
				$thumbs .= $relatedWikihow->createFastRenderHtml();
			} elseif ( $this->mMobile ) {
				$thumbs .= $relatedWikihow->createMobileHtml();
			} else {
				$thumbs .= $relatedWikihow->createDesktopHtml();
			}
		}

		if ( $insertAd ) {
			// get the ad now
			$thumbs .= $this->mAd;
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );

		$contents = Html::rawElement( "div", array( 'id' => 'relatedwikihows', 'class' => 'section_text' ), $thumbs.$clear );

		$section = Html::rawElement( "div", array( 'class' => [ 'section', 'relatedwikihows', 'sticky' ],  ), $heading.$contents );

		return $section;
	}

	private function okToShowSection() {
		return $this->mShowSection;
	}

	// takes the html of the related wikihows and adds it to the current php query document
	public function addRelatedWikihowsSection() {
		if ( !$this->okToShowSection() ) {
			return;
		}
		$relatedHtml = $this->getSectionHtml();

		if ( !$relatedHtml ) {
			return;
		}

		$prevSection = null;
		// remove existing section if it exists (we already have the data we need from it)
		$sectionSelector = ".".self::getSectionName();
		if ( pq( $sectionSelector )->length > 0 ) {
			// get the prev setion so we can insert the new one after it
			$prevSection = pq( $sectionSelector. ":first" )->prev();
		}

		pq( $sectionSelector )->remove();

		// try to put the related wikihows section back where it was
		// if we have created it new, then put it before 'About this wikiHow' (#aboutthisarticle) on mobile
		// or before sourcesandcitations on desktop
		// or just put it as the last section if these other sections do not exist
		if ( $prevSection && $prevSection->length > 0  ) {
			pq( $prevSection )->after( $relatedHtml );
		} elseif (!$this->mMobile && pq( ".section.sourcesandcitations" )->length > 0 ) {
			pq( ".section.sourcesandcitations" )->before( $relatedHtml );
		} else if (!$this->mMobile && pq( ".section.references" )->length > 0 ) {
			pq( ".section.references" )->before( $relatedHtml );
		} else if ( pq( "#aboutthisarticle" )->length > 0 ) {
			pq( "#aboutthisarticle" )->before( $relatedHtml );
		} else {
			pq( ".section:last" )->after( $relatedHtml );
		}
	}

	// get 4 related wikihows to show in the side bar
	public function getSideData() {
		$header = Html::element( 'h3', array(), wfMessage('relatedarticles')->text() );

		$relatedWikihows = $this->mRelatedWikihows;

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$relatedWikihows = array_slice( $relatedWikihows, 0, 4 );

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			$thumbs .= $relatedWikihow->createSidebarHtml();
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );

		$html = $header.$thumbs.$clear;
		return $html;
	}

	//function we use when we're not inserting via the php Query object
	public function getRelatedHtml() {
		$relatedWikihows = $this->mRelatedWikihows;

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$num = $this->mMobile ? 2 : 4;
		$relatedWikihows = array_slice( $relatedWikihows, 0, $num );

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			$thumbs .= $this->mMobile ? $relatedWikihow->createMobileHtml() : $relatedWikihow->createDesktopHtml();
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );
		$html = Html::rawElement( "div", array( 'id' => 'qa_related_box' ), $thumbs.$clear );

		return $html;
	}

	public static function clearArticleMemc( $wikiPage ) {
		global $wgMemc;
		if ( !$wikiPage ) {
			return;
		}

		$title = $wikiPage->getTitle();
		if ( !$title ) {
			return;
		}

		$cachekey = wfMemcKey( self::MEMCACHED_KEY, $title->getArticleID() );
		$wgMemc->delete( $cachekey );
	}

	// return the name of this section id
	public static function getSectionName() {
		$relatedsname = wfMessage('relatedwikihows')->text();
		$relatedsname = mb_strtolower($relatedsname); //make it lowercase
		$relatedsname = preg_replace('/[\s\p{P}]/u', '', $relatedsname); //remove spaces and punctuation
		return $relatedsname;
	}
}

class SensitiveRelatedWikihows {

	/*
	 * CREATE TABLE `sensitive_related_page` (
	 * `srp_page_id` int(10) unsigned NOT NULL,
	 * PRIMARY KEY (`srp_page_id`)
	 * );
	 * CREATE TABLE `sensitive_related_remove_page` (
	 * `srrp_page_id` int(10) unsigned NOT NULL,
	 * PRIMARY KEY (`srrp_page_id`)
	 * );
	 */

	const FEED_LINK = "https://spreadsheets.google.com/feeds/list/";
	const SHEET_ID = "1JCuh-aB-HxvZM-pKpaEIGFCrz7Er0c6fFyLM8qaWEK0";
	const FEED_LINK_2 = "/private/values?alt=json&access_token=";
	const SENSITIVE_RELATED_PAGE_TABLE = "sensitive_related_page";
	const SENSITIVE_RELATED_REMOVE_PAGE_TABLE = "sensitive_related_remove_page";

	public static function saveSensitiveRelatedArticles() {
		global $wgIsDevServer;

		$sheetId = $wgIsDevServer ? '1jxVrhC7lk3TYJaQ2iBwEJXThBfVkE5qAR1zrfgOeHUo' : self::SHEET_ID;

		$sheetData = GoogleSheets::getRows($sheetId, 'Remove list!A2:C');
		$removeList = self::parseRemoveList( $sheetData );
		$result = self::saveRemoveList( $removeList );

		$sheetData = GoogleSheets::getRows($sheetId, 'Sensitive master!A2:C');
		$sensitiveMasterList = self::parseSensitiveMaster( $sheetData );
		$result .= self::saveSensitiveMasterList( $sensitiveMasterList );
		return $result;
	}

	/*
	 * saves the list of pages from sensitive related wikihows remove tab
	 *
	 * @param Array $data array which has key of lang code and value of array of pageids
	 */
	private static function saveRemoveList( $data ) {
		global $wgWikiHowLanguages;

		// initialize with en data..for some reason this is not in the wgLanguages global used below
		$updateData = array('en' => isset( $data['en'] ) ? $data['en'] : array() );

		foreach ( $wgWikiHowLanguages as $lang ) {
			if ( isset( $data[$lang] ) ) {
				$updateData[$lang] = $data[$lang];
			} else {
				$updateData[$lang] = array();
			}
		}

		$table = self::SENSITIVE_RELATED_REMOVE_PAGE_TABLE;
		$fieldName = 'srrp_page_id';
		$message = '';
		foreach ( $updateData as $lang => $pageIds ) {
			$resultMessage = self::saveNewIdsRemoveDeleteIds( $lang, $pageIds, $table, $fieldName );
			if ( $resultMessage ) {
				$message = $message . "\n" . $resultMessage;
			}
		}
		return $message;
	}

	/*
	 * saves all translations of pageId from EN to it's languages
	 * with data read from the sensitive related wikihows master tab
	 *
	 * updates the sensitive_related_page table with these pages
	 * and removes items which are no longer there
	 *
	 * @param Array $data array which has arrays of the form (pageid, articleurl)
	 * right now assumes EN although that may change in the future so we do read in the articleurl
	 * although we do not actually use it right now
	 */
	private static function saveSensitiveMasterList( $sheetData ) {
		global $wgWikiHowLanguages;
		$message = "";
		if ( !$sheetData ) {
			$message = "no items to remove";
			//decho( $message );
			return $message;
		}
		$data = array();
		// get translation page ids for each item in the list
		foreach ( $sheetData as $pageInfo ) {
			if ( !$pageInfo[0] ) {
				continue;
			}
			$data['en'][] = $pageInfo[0];
			$links = TranslationLink::getLinksTo( 'en', $pageInfo[0] );
			foreach ( $links as $link ) {
				$data[$link->toLang][] = $link->toAID;
			}
		}

		// initialize with en data..for some reason this is not in the wgLanguages global used below
		$updateData = array('en' => isset( $data['en'] ) ? $data['en'] : array() );

		foreach ( $wgWikiHowLanguages as $lang ) {
			if ( isset( $data[$lang] ) ) {
				$updateData[$lang] = $data[$lang];
			} else {
				$updateData[$lang] = array();
			}
		}

		$table = self::SENSITIVE_RELATED_PAGE_TABLE;
		$fieldName = 'srp_page_id';
		$message = '';
		foreach ( $updateData as $lang => $pageIds ) {
			$resultMessage = self::saveNewIdsRemoveDeleteIds( $lang, $pageIds, $table, $fieldName );
			if ( $resultMessage ) {
				$message = $message . "\n" . $resultMessage;
			}
		}
		return $message;
	}

	private static function parseRemoveList( $data ) {
		$result = array();
		if ( !$data ) {
			return $result;
		}
		foreach ( $data as $row ) {
			$lang = $row[0]; // column name: Language
			$pageId = $row[1]; // column name: ID
			$result[$lang][] = $pageId;
		}

		return $result;
	}

	private static function parseSensitiveMaster( $data ) {
		$result = array();
		foreach ( $data as $row ) {
			$pageId = $row[0]; // column name: ID
			$url = $row[1]; // column name: URL
			$result[] = array( $pageId, $url );
		}

		return $result;
	}

	/*
	 * used to update items from a google sheet into the db into a table which is simply pageId
	 *
	 * @param string $lang the lang to use to get the lang database
	 * @param Array $pageIds list of pages ids to be saved
	 * @param string $table the name of the table to be used
	 * @param string $fieldName the name of the field which contains the pageId on $table
	 */
	private static function saveNewIdsRemoveDeleteIds( $lang, $pageIds, $table, $fieldName ) {
		$dbw = wfGetDB( DB_MASTER );

		$langDB = Misc::getLangDB( $lang );
		if ( !$langDB ) {
			$message = "could not get lang db for:". $lang;
			//decho( $message );
			return $message;
		}
		$table = $langDB . '.' . $table ;
		$cond = array();

		$var = "$fieldName as page_id";
		$res = $dbw->select( $table, $var, $cond, __METHOD__ );
		$existing = array();
		foreach ( $res as $row ) {
			$existing[] = $row->page_id;
		}

		$removeIds = array_unique( array_values( array_diff( $existing, $pageIds ) ) );
		$insertIds = array_unique( array_values( array_diff( $pageIds, $existing ) ) );
		//$removeIds = array_diff( $existing, $pageIds );
		//$insertIds = array_diff( $pageIds, $existing );

		$message = '';
		if ( $removeIds ) {
			//decho("field: $fieldName lang: $lang remove ids", json_encode( $removeIds ) );
			$deleteCond = array( $fieldName => $removeIds );
			$dbw->delete( $table, $deleteCond, __METHOD__ );
			$message = "updated $table for $lang\n";
		}
		if ( $insertIds ) {
			//decho("field: $fieldName lang: $lang insert ids", json_encode( $insertIds ) );
			$insertData  = array();
			foreach ( $insertIds as $id ) {
				$insertData[] = array( $fieldName => $id );
			}

			$dbw->insert( $table, $insertData, __METHOD__ );
			$removeCount = count( $removeIds );
			$insertCount = count( $insertIds );
			$message .= "updated $table for $lang. $removeCount items removed. $insertCount items added.\n";
		}
		if ( !$message ) {
			$message = "no updates for $table for $lang\n";
		}


		$var = "count(*)";
		$removeCount = $dbw->selectField( $table, $var, $cond );
		if ( $fieldName == 'srrp_page_id' ) {
			$type = "remove pages";
		} else {
			$type = "master pages";
		}

		$message .= "number of $type for $lang is: $removeCount\n";
		return $message;
	}

	/*
	 * checks is this page is a sensitive related wikihows master page
	 *
	 * @param Title $title title of the page we are checking
	 */
	public static function isSensitiveRelatedRemovePage( $title ) {
		global $wgLanguageCode;
		if ( !$title ) {
			return false;
		}
		$pageId = $title->getArticleID();
		$dbr = wfGetDB( DB_REPLICA );
		$table = self::SENSITIVE_RELATED_REMOVE_PAGE_TABLE;
		$var = 'count(*)';
		$conds = array(
			'srrp_page_id' => $pageId,
		);
		$options = array();

		$count = $dbr->selectField( $table, $var, $conds, __METHOD__, $options );
		if ( $count ) {
			return true;
		}
		return false;
	}

	public static function isSensitiveRelatedPage( $pageId ) {
		global $wgLanguageCode;
		$dbr = wfGetDB( DB_REPLICA );
		$table = self::SENSITIVE_RELATED_PAGE_TABLE;
		$var = 'count(*)';
		$conds = array(
			'srp_page_id' => $pageId,
		);
		$options = array();
		$count = $dbr->selectField( $table, $var, $conds, __METHOD__, $options );
		if ( $count ) {
			return true;
		}
		return false;
	}
}
