<?php

class Ad {
	var $mHtml;
	var $mType;

	public function __construct( $type ) {
		$this->mType = $type;
	}
}

/*
 * default setup for the ad creators
 */
abstract class AdCreator {
	var $mDFPKeyVals = array();
	var $mRefreshableRightRail = false;
	var $mAdCounts = array();
	var $mGptSlotDefines = array();
	var $mDFPData = array();
	var $mLateLoadDFP = false;

	var $mAdsenseChannels = array();
	var $mMobileAdsenseChannels = array();

	public function getPreContentAdHtml() {
		return "";
	}

	private function getAdTargetId( $type ) {
		if ( !isset( $this->mAdCounts[$type] ) ) {
			$this->mAdCounts[$type] = 0;
		}

		$this->mAdCounts[$type] += 1;

		return $type . '_ad_' . $this->mAdCounts[$type];
	}

	/*
	 * sets the channels and mobilechannels data attributes for the ad
	 * this will be used later when creating the ad html element
	 * it also sets extra channels for testing based on the bucket of the test
	 * TODO we could possibly improve this by returning early if this is not an adsense ad
	 * however we would have to check more than just the 'type' attribue but also 'smalltype'
	 */
	private function setAdChannels( &$ad ) {
		// create the channel attributes if they are not there already
		// these would likely contain ad specific channels
		if ( !isset( $ad->setupData['channels'] ) ) {
			$ad->setupData['channels'] = array();
		}
		if ( !isset( $ad->setupData['mobilechannels'] ) ) {
			$ad->setupData['mobilechannels'] = array();
		}

		// add any bucket specific channels
		if ( intval( $this->mBucketId ) >= 11 ) {
			$ad->setupData['channels'][] = 5941219836;
			$ad->setupData['mobilechannels'][] = 5941219836;
		} else {
			$ad->setupData['channels'][] = 8567383174;
			$ad->setupData['mobilechannels'][] = 8567383174;
		}

		// add any channels that were set for this ad creator object
		$ad->setupData['channels'] =  array_merge( $ad->setupData['channels'], $this->mAdsenseChannels);
		$ad->setupData['mobilechannels'] =  array_merge( $ad->setupData['mobilechannels'], $this->mMobileAdsenseChannels);

		// add ad channels based on our bucket number. 0 will never be used because buckets are 1-24
		$bucketToChannel = [
			0,
			1179844797,
			8866763126,
			4314124659,
			3001042984,
			3614436449,
			7362109766,
			6049028097,
			5232454650,
			3919372983,
			7667046301,
			3727801297,
			2414719621,
			8788556287,
			7475474614,
			8596984590,
			4657739586,
			9718494570,
			7092331233,
			5779249560,
			3153086224,
			9526922888,
			8213841216,
			6900759545,
			7929936032,
		];
		$bucketInt = intval( $this->mBucketId );
		$ad->setupData['channels'][] = $bucketToChannel[$bucketInt];
	}

	protected function getNewAd( $type ) {

		$ad = new Ad( $type );

		if ( isset ($this->mAdSetupData[$type] ) ) {
			$ad->setupData = $this->mAdSetupData[$type];
		}

		$ad->mTargetId = $this->getAdTargetId( $type );

		$this->setAdChannels( $ad );

		return $ad;
	}

	/*
	 * get json string of the dfp key vals for use in js
	 */
	public function getDFPKeyValsJSON() {
		$dfpKeyVals = $this->mDFPKeyVals;

		if ($this->mAdSetupData) {
			foreach ( $this->mAdSetupData as $adType => $adData ) {
				if ( !isset( $this->mAdSetupData[$adType]['service'] ) ) {
					continue;
				}
				if ( $this->mAdSetupData[$adType]['service'] != 'dfp' ) {
					continue;
				}
				if ( !isset( $this->mAdSetupData[$adType]['refreshable'] ) ) {
					continue;
				}
				$adUnitPath = $this->mAdSetupData[$adType]['adUnitPath'];
				$dfpKeyVals[$adUnitPath]['refreshing'] = '1';
			}
		}

		$dfpKeyVals = json_encode( $dfpKeyVals );
		return $dfpKeyVals;
	}

	/*
	 *  adds the intro ad to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertIntroAd() {
		$ad = $this->getBodyAd( 'intro' );

		if ( !$ad ) {
			return;
		}

		pq( "#intro" )->append( $ad->mHtml )->addClass( "hasad" );
	}

	/*
	 * adds the scrollto ads to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertScrollToAd() {
		$ad = $this->getBodyAd( 'scrollto' );
		if ( !$ad ) {
			return;
		}
		pq( "#intro" )->after( $ad->mHtml );
	}

	/*
	 * adds the quiz ads to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertQuizAds() {
		for ( $i = 0; $i < pq( '.qz_container' )->length; $i++ ) {
			$ad = $this->getBodyAd( 'quiz' );
			if ( !$ad ) {
				continue;
			}
			pq( '.qz_container' )->eq($i)->append( $ad->mHtml );
		}
	}

	protected function insertRewardedWebAds() {
		global $wgTitle;
		if ( $wgTitle->getArticleID() !== 41306 ) {
			return;
		}

		$ad = $this->getBodyAd( 'rewardedweb' );
		if ( !$ad ) {
			return;
		}

		if ( pq( '.green_box' )->eq( 1 )->length == 0 ) {
			return;
		}
		$href = pq( '.green_box' )->eq( 1 )->find( 'a:first' )->attr( 'href' );
		$link = Html::element( 'a', ['rel' => 'nofollow', 'id' => 'rewardedweb-link', 'href' => $href], "this video" );
		$inner = "<p><b>Take it a step further:</b> Watch <b>$link</b> for access to our origami class</p>";
		$html = Html::rawElement( 'div', ['id' => 'rewardedweb', 'class' => 'rewardedweb green_box' ], $inner );
		//$class = pq( '.green_box' )->eq( 1 )->attr('class');
		pq( '.green_box' )->eq( 1 )->attr( 'id', 'rewardedweb-original' );
		pq( '.green_box' )->eq( 1 )->after( $html );
	}

	protected function insertTocAd( $stepsListFirst ) {
		if ( !$stepsListFirst ) {
			return;
		}
		if ( !count( $stepsListFirst->childNodes ) ) {
			return;
		}
		$ad = $this->getBodyAd( 'toc' );
		if ( !$ad ) {
			return;
		}

		$stepsListFirstLiFirst = $stepsListFirst->childNodes[0];

		pq( $stepsListFirstLiFirst )->append( $ad->mHtml );
	}

	protected function insertRelatedAd() {
		$ad = $this->getBodyAd( 'related' );
		if ( !$ad ) {
			return;
		}

		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			// if not found try to get related wikihows target name in another way
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}

		if ( pq( $target )->length == 0 ) {
			return;
		}

		pq( $target )->append( $ad->mHtml );
	}

	protected function insertMobileRelatedAd() {
		$ad = $this->getBodyAd( 'mobilerelated' );
		if ( !$ad ) {
			return;
		}

		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			// if not found try to get related wikihows target name in another way
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}

		if ( pq( $target )->length == 0 ) {
			return;
		}

		pq( $target )->append( $ad->mHtml );
	}

	protected function insertMiddleRelatedAd() {
		$ad = $this->getBodyAd( 'middlerelated' );
		if ( !$ad ) {
			return;
		}
		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			// if not found try to get related wikihows target name in another way
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}

		if ( pq( $target )->length == 0 ) {
			return;
		}

		if ( pq( '.related-article' )->length ) {
			pq( $target )->find( '.related-article:eq(1)' )->after( $ad->mHtml );
		} else if ( pq( '.related-wh' )->length ) {
			pq( $target )->find( '.related-wh:eq(1)' )->after( $ad->mHtml );
		}
	}

	protected function insertQAAd() {
		$ad = $this->getBodyAd( 'qa' );
		if ( !$ad ) {
			return;
		}
		$target = "#qa";
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertMobileQAAd() {
		$ad = $this->getBodyAd( 'mobileqa' );
		if ( !$ad ) {
			return;
		}
		$target = "#qa";
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( '<br>'.$ad->mHtml );
	}

	protected function insertTipsAd() {
		$ad = $this->getBodyAd( 'tips' );
		if ( !$ad ) {
			return;
		}
		$target = mb_strtolower( wfMessage( 'tips' )->text() );
		$target = "div#".$target;
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( '<br>'.$ad->mHtml );
	}

	protected function insertWarningsAd() {
		$ad = $this->getBodyAd( 'warnings' );
		if ( !$ad ) {
			return;
		}
		$target = mb_strtolower( wfMessage( 'warnings' )->text() );
		$target = "div#".$target;
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertPageBottomAd() {
		$ad = $this->getBodyAd( 'pagebottom' );
		if ( !$ad ) {
			return;
		}
		$bottomAdContainer = Html::element( 'div', ['id' => 'pagebottom'] );
		if ( pq( '#article_rating_mobile' )->length == 0 ) {
			return;
		}

		pq('#article_rating_mobile')->after( $bottomAdContainer );
		$target = "#pagebottom";
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertStepAd( $stepsListFirst ) {
		$ad = $this->getBodyAd( 'step' );
		if ( !$ad ) {
			return;
		}

		if ( !$stepsListFirst ) {
			return;
		}

		$liCount = 0;
		$firstLiIndex = null;
		for( $i = 0; $i < count( $stepsListFirst->childNodes ); $i++ ) {
			if ( $stepsListFirst->childNodes[$i]->tagName == 'li' ) {
				$liCount++;
				if ( $firstLiIndex === null ) {
					$firstLiIndex = $i;
				}
			}
		}

		if ( !$liCount ) {
			return;
		}

		$stepsListFirstLiFirst = $stepsListFirst->childNodes[$firstLiIndex];
		pq( $stepsListFirstLiFirst )->append( $ad->mHtml );
	}

	protected function insertMobileMethodAds( $stepsList ) {
		foreach ( $stepsList as $step ) {
			$liCount = 0;
			$lastLiIndex = 0;
			for( $i = 0; $i < count( $step->childNodes ); $i++ ) {
				if ( $step->childNodes[$i]->tagName == 'li' ) {
					$liCount++;
					$lastLiIndex = $i;
				}
			}

			if ( !$liCount ) {
				continue;
			}
			$ad = $this->getBodyAd( 'mobilemethod' );
			if ( !$ad ) {
				continue;
			}
			$stepLiLast = $step->childNodes[$lastLiIndex];

			pq( $stepLiLast )->append( $ad->mHtml );
		}
	}

	protected function insertMethodAd( $stepsListFirst ) {
		if ( !$stepsListFirst ) {
			return;
		}

		$liCount = 0;
		$lastLiIndex = 0;
		for( $i = 0; $i < count( $stepsListFirst->childNodes ); $i++ ) {
			if ( $stepsListFirst->childNodes[$i]->tagName == 'li' ) {
				$liCount++;
				$lastLiIndex = $i;
			}
		}

		if ( $liCount <= 2 ) {
			return;
		}

		$stepsListFirstLiLast = $stepsListFirst->childNodes[$lastLiIndex];

		$ad = $this->getBodyAd( 'method' );
		if ( !$ad ) {
			return;
		}

		pq( $stepsListFirstLiLast )->append( $ad->mHtml );
	}

	protected function insertMethod2Ad() {
		$methodNumber = 2;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method2' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod3Ad() {
		$methodNumber = 3;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method3' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod4Ad() {
		$methodNumber = 4;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method4' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod5Ad() {
		$methodNumber = 5;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method5' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethodExtraAd() {
		$numMethods = pq( ".steps_list_2" )->length;

		// this ad only gets inserted if there are more than 6 methods
		if ( $numMethods <= 6 ) {
			return;
		}

		$start = 5;
		$end = $numMethods - 1;
		for ( $i = $start; $i < $end; $i++ ) {
			$ad = $this->getBodyAd( 'methodextra' );
			if ( !$ad ) {
				continue;
			}
			pq( ".steps_list_2:eq($i) > li:last-child" )->append( $ad->mHtml );
		}
	}

	protected function insertMethodLastAd() {
		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods <= 5 ) {
			return;
		}

		$methodNumber = $numMethods - 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'methodlast' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	/*
	 * uses php query to put the ad html into the body of the page
	 */
	public function insertAdsInBody() {
		global $wgOut;

		// reset ad counts
		$this->mAdCounts = array();

		// make sure we have php query object
		if ( !phpQuery::$defaultDocumentID )  {
			return;
		}

		if ( GoogleAmp::isAmpMode( $wgOut ) ) {
			GoogleAmp::insertAMPAds();
			return;
		}

		$stepsList = pq( ".steps_list_2" );
		$stepsListFirst = null;
		if ( count( $stepsList->elements ) ) {
			$stepsListFirst = $stepsList->elements[0];
		}

		$this->insertIntroAd();
		$this->insertTocAd( $stepsListFirst );
		$this->insertMethodAd( $stepsListFirst );
		$this->insertMobileMethodAds( $stepsList );
		$this->insertScrollToAd();
		$this->insertStepAd( $stepsListFirst );
		$this->insertRelatedAd();
		$this->insertMobileRelatedAd();
		$this->insertMiddleRelatedAd();
		$this->insertQAAd();
		$this->insertMobileQAAd();
		$this->insertTipsAd();
		$this->insertWarningsAd();
		$this->insertPageBottomAd();
		$this->insertQuizAds();
		$this->insertRewardedWebAds();
	}

	public function __construct() {
		global $wgTitle, $wgRequest;
		$this->mPageId = 0;
		if ( $wgTitle ) {
			$this->mPageId = $wgTitle->getArticleID();
		}
		$bucket = rand( 1, 20 );
		if ( $bucket == 20 ) {
			$extra = rand( 0, 4 );
			$bucket += $extra;
		}
		if ( $wgRequest && $wgRequest->getInt( 'bucket' ) ) {
			$reqBucket = $wgRequest->getInt( 'bucket' );
			if ( $reqBucket > 0 && $reqBucket <= 24 ) {
				$bucket = $reqBucket;
			}
		}
		$this->mBucketId = sprintf( "%02d", $bucket );
	}

	public function isAdOkForDomain( $ad ) {
		global $wgOut;
		$result = true;

		if ( !Misc::isMobileMode() ) {
			// on desktop domain only show ads with large
			if ( $ad->setupData['large'] !== 1 ) {
				$result = false;
			}
		}

		return $result;
	}

	public function getBodyAd( $type ) {
		$ad = $this->getNewAd( $type );

		if ( !isset( $ad->setupData ) ) {
			return null;
		}

		// get the inner ad
		$innerClass = $ad->setupData['innerclass'] ?:'';
		if ( is_array( $innerClass ) && $this->showBlockthroughJs() ) {
			$innerClass[] = "blockthrough";
		}

		$attributes = array(
			'id' => $ad->mTargetId,
			'class' => $innerClass,
		);
		$innerHtml = '';
		$skipInlineHtml = true;

		if ( $type == 'toc' && !WikihowToc::isNewArticle() ) {
			return;
		}

		if ( $type == 'intro' && class_exists( "TechLayout" ) && ArticleTagList::hasTag( TechLayout::CONFIG_LIST, $this->mPageId ) ) {
			return;
		}

		if ( !$this->isAdOkForDomain( $ad ) ) {
			return;
		}

		$innerAdHtml = Html::rawElement( 'div', $attributes, $innerHtml );

		// now the wrapper
		$attributes = array(
			'class' => array( 'wh_ad_inner' ),
		);

		// all the settings for the ad come from the adSetupData
		foreach ( $ad->setupData as $key => $val ) {
			if ( $key == 'class' ) {
				foreach ( $val as $classVal ) {
					$attributes['class'][] = $classVal;
				}
			} elseif ( $key == 'smalllabel' ) {
				$label = $this->getAdLabelText();
				$innerAdHtml .= Html::rawElement( 'div', [ 'class' => 'ad_label_method' ], $label );
			} elseif ( $key == 'channels' ) {
				$val = implode(',', $val);
				$adKey = 'data-'.$key;
				$attributes[$adKey] = $val;
			} elseif ( $key == 'mobilechannels' ) {
				$val = implode(',', $val);
				$adKey = 'data-'.$key;
				$attributes[$adKey] = $val;
			} elseif ( $key == 'containerheight' ) {
				$attributes['style'] = "height:{$val}px";
			} else {
				if ( is_array( $val ) ) {
					$val = implode(' ', $val);
				}
				$adKey = 'data-'.$key;
				$attributes[$adKey] = $val;
			}
		}

		$attributes['data-observerloading'] = 1;

		$attributes['data-sizes-array'] = $attributes['data-size'];
		$attributes['data-gptdisplaylate'] = 1;

		$html = Html::rawElement( 'div', $attributes, $innerAdHtml );

		$html .= Html::inlineScript( "WH.ads.addBodyAd('{$ad->mTargetId}')" );

		$ad->mHtml = $html;

		return $ad;
	}

	// gets the js snippet used to load an adsense ad
	// it is used if we inline the html insteaad of lazy load it
	// currently only works for desktop channels since it only looks at data-channels
	// not data-mobilechannels
	private function getAdsByGoogleJS( $ad ) {
		$channels = $ad->setupData['channels'];
		$channels = implode( ',', $channels );
		$script = "(adsbygoogle = window.adsbygoogle || []).push({";
		if ( $channels ) {
			$script .= "params: {google_ad_channel: '$channels'}";
		}
		$script .= "});";
		return $script;
	}

	protected function getInitialRefreshSnippetGPT() {
		//get initial ad refresh slots snippet to request them both in one call
		$refreshSlots = array();
		foreach ( $this->mAds as $type => $ad ) {
			if (  !( $ad ) ) {
				continue;
			}
			if ( $ad->service != 'dfp' ) {
				continue;
			}
			if ( $this->getApsLoad( $ad ) ) {
				continue;
			}
			$id = $ad->targetId;
			$refreshSlots[] = "gptAdSlots['$id']";
		}
		if ( !count( $refreshSlots ) ) {
			return "";
		}
		$refreshParam = implode( ",", $refreshSlots );
		$dfpTargeting = '';
		foreach ( $refreshSlots as $slot ) {
			$dfpTargeting .= 'setDFPTargeting('.$slot.', dfpKeyVals);';
		}
	}

	// returns a javascript snippet of the ad unit path
	// instead of just a string to prevetn google bot from crawling these paths
	protected function getGPTAdSlot( $path ) {
		$adUnitPath = str_replace( "/", "|", $path);
		$adUnitPath = "'".$adUnitPath . "'.replace(/\|/g,'/')";
		return $adUnitPath;
	}

	private function getGPTAdSize( $ad ) {
		$adSize = $this->mDFPData[$ad->mType]['size'];
		return $adSize;
	}

	protected function getApsLoad( $ad ) {
		if ( !isset( $this->mDFPData[$ad->mType]['apsLoad'] ) ) {
			return false;
		}
		return $this->mDFPData[$ad->mType]['apsLoad'];
	}

	/*
	 * get js snippet to refresh the first set of ads in a single call
	 */
	protected function getInitialRefreshSnippetApsLoad() {
		$slotIds = array();
		$apsSlots = array();
		foreach ( $this->mAds as $type => $ad ) {
			if ( !$ad ) {
				continue;
			}
			if ( $ad->service != 'dfp' ) {
				continue;
			}
			if ( !$this->getApsLoad( $ad ) ) {
				continue;
			}
			$slotId = $ad->targetId;
			$slotIds[] = "'".$slotId."'";
			$slotName = $this->getGPTAdSlot( $ad );
			$slotSizes = $this->getGPTAdSize( $ad );
			$apsSlots[] = "{slotID: '$slotId', slotName: $slotName, sizes: $slotSizes}";
		}
		if ( !count( $slotIds ) ) {
			return "";
		}
		$apsSlots = "[" . implode( ",", $apsSlots ) . "]";
		$gptSlotIds = "[" . implode( ",", $slotIds ) . "]";
		// TODO get the timeout time from the ad itself
		$html = Html::inlineScript("googletag.cmd.push(function(){WH.ads.apsFetchBids($apsSlots, $gptSlotIds, 2000);});");
		return $html;
	}

	/*
	 * get js snippet to refresh the first set of ads in a single call
	 */
	// TODO this is not used anymore refactor/remove it
	protected function getInitialRefreshSnippet() {
		$html = $this->getInitialRefreshSnippetApsLoad();
		$html .= $this->getInitialRefreshSnippetGPT();
		return $html;
	}

	public function getHeadHtmlOld() {
		$addAdsense = false;
		$addDFP = false;
		$apsLoad = false;
		$prebidLoad = false;

		if ( empty( $this->mAdSetupData ) ) {
			return;
		}

		foreach ( $this->mAdSetupData as $adType => $adData ) {
			$service = $adData['service'];

			if ( $service == "adsense" ) {
				$addAdsense = true;
			}

			if ( $service == "dfp" ) {
				// if we are in mobile mode, then only add dfp if the ad is small or medium
				$addDFP = true;
			}

			$apsLoad = $apsLoad || $adData['apsLoad'];
			$prebidLoad = $prebidLoad || $adData['prebidLoad'];
		}

		$scripts = [];
		if ( $addAdsense ) {
			$scripts[] = file_get_contents( __DIR__."/adsenseSetup.compiled.js" );
		}

		// some setups do not allow dfp ads at all so let them override it here
		if ( !$this->isDFPOkForSetup() ) {
			$addDFP = false;
		}
		if ( $addDFP ) {
			if ( !$prebidLoad ) {
				$indexScript = $this->getIndexHeadScript();
				if ( $indexScript ) {
					$scripts[] = $indexScript;
				}
			}
			if ( $this->mLateLoadDFP == false ) {
				$category = $this->getCategoryForDFP();
				$isCoppa = self::getCoppaValue();
				$loadGPT = $this->loadGPT();
				$dfpSmallTest = 'false';
				if ( $this->isDFPSmallTest() ) {
					$dfpSmallTest = 'true';
				}
				//$dfpScript .= '<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>';
				$dfpScript = "var bucketId = '$this->mBucketId';";
				$dfpScript .= "var loadGPT = $loadGPT;";
				$dfpScript .= "var dfpSmallTest = $dfpSmallTest;";
				$dfpScript .= "var dfpCategory = '$category';";
				$dfpScript .= "var isCoppa = '$isCoppa';";
				$dfpScript .= "\n";
				if ( $prebidLoad ) {
					// for now include the non compiled version of dfpinit so it is easier to debug
					$dfpScript .= file_get_contents( __DIR__."/DFPinit.compiled.js" );
					$dfpScript .= file_get_contents( __DIR__."/prebidinit.js" );
				} else {
					$dfpScript .= file_get_contents( __DIR__."/DFPinit.compiled.js" );
				}
				if ( $apsLoad ) {
					$dfpScript .= file_get_contents( __DIR__."/APSinit.compiled.js" );
				}
				$scripts[] = $dfpScript;
			}
		}

		$scripts = Html::inlineScript( implode( $scripts ) );
		$styles = $this->getAdLabelStyle();
		$result = $scripts . $styles;

		return $result;
	}

	/*
	 * gets script for adsense and dfp
	 * @return string html for head
	 */
	public function getHeadHtml() {
		$addAdsense = false;
		$addDFP = false;
		$apsLoad = false;
		$prebidLoad = false;
		$addKaiAds = false;

		if ( empty( $this->mAdSetupData ) ) {
			return;
		}

		foreach ( $this->mAdSetupData as $adType => $adData ) {
			$service = $adData['service'];

			if ( $service == "adsense" ) {
				$addAdsense = true;
			}

			if ( $service == "dfp" ) {
				$addDFP = true;
			}

			if ( $service == "kaiads" ) {
				$addKaiAds = true;
			}

			$apsLoad = $apsLoad || $adData['apsLoad'];
			$prebidLoad = $prebidLoad || $adData['prebidLoad'];
		}

		if ($addKaiAds) {
			return;
		}

		$scripts = [];
		if ( $addAdsense ) {
			$scripts[] = __DIR__."/adsenseSetup.compiled.js";
		}

		// some setups do not allow dfp ads at all so let them override it here
		if ( !$this->isDFPOkForSetup() ) {
			$addDFP = false;
		}
		$dfpScript = "";
		if ( $addDFP ) {
			if ( !$prebidLoad ) {
				$scripts[] = __DIR__."/IndexExchangeInit.compiled.js";
			}
			if ( $this->mLateLoadDFP == false ) {
				$category = $this->getCategoryForDFP();
				$isCoppa = self::getCoppaValue();
				$loadGPT = $this->loadGPT();
				$dfpSmallTest = 'false';
				if ( $this->isDFPSmallTest() ) {
					$dfpSmallTest = 'true';
				}
				$dfpScript = "window.bucketId = '$this->mBucketId';";
				$dfpScript .= "window.loadGPT = $loadGPT;";
				$dfpScript .= "window.dfpSmallTest = $dfpSmallTest;";
				$dfpScript .= "window.dfpCategory = '$category';";
				$dfpScript .= "window.isCoppa = '$isCoppa';";
				$dfpScript .= "\n";
				$scripts[] = __DIR__."/DFPinit.compiled.js";
				if ( $prebidLoad ) {
					$scripts[] = __DIR__."/prebidinit.js";
				}
				if ( $apsLoad ) {
					$scripts[] = __DIR__."/APSinit.compiled.js";
				}
			}
		}

		$scriptsOutput = '';
		if ( $dfpScript ) {
			$scriptsOutput .= Html::inlineScript( $dfpScript );
		}
		if ( $scripts ) {
			$scriptsOutput .= Html::inlineScript( Misc::getEmbedFiles( "js", $scripts ) );
		}
		$styles = $this->getAdLabelStyle();
		$result = $scriptsOutput . $styles;

		return $result;
	}

	protected function isDFPSmallTest() {
		if ( Misc::isFastRenderTest() ) {
			return false;
		}
		return false;
	}

	private function loadGPT() {
		return 1;
	}

	private function getCategoryForDFP() {
		global $wgTitle;
		$catList = SchemaMarkup::getCategoryListForBreadcrumb( $wgTitle );
		if ( count( $catList ) == 0 ) {
			return '';
		}
		$first = $catList[0];
		if ( count( $first ) == 0 ) {
			return '';
		}
		$text = $first[0]->getText();
		$text = strtolower( $text );
		$text = str_replace( ' ', '_', $text );
		if ( !$text ) {
			$text = false;
		}
		return $text;
	}

	protected function isDFPOkForSetup() {
		return true;
	}

	protected function getAdLabelStyle() {
		// get any extra css for ad labels.. the text or the font size etc
		$labelClassName = ".ad_label";
		$labelText = $this->getAdLabelText();
		$css = "{$labelClassName}:before{content:'$labelText';}";
		$css = Html::inlineStyle( $css );
		return $css;
	}

	protected function getAdLabelText() {
		$labelText = wfMessage( 'ad_label' );
		return $labelText;
	}

	protected function getIndexHeadScript() {
		global $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return '';
		}
		$result = file_get_contents( __DIR__."/IndexExchangeInit.compiled.js" );
		return $result;
	}

	protected function addRewardedWebToGPTDefines( $ad ) {
		$adUnitPath = $ad->setupData['adUnitPath'];
		$gpt = "const rewardedSlot = googletag.defineOutOfPageSlot('$adUnitPath', googletag.enums.OutOfPageFormat.REWARDED).addService(googletag.pubads());\n";
		$gpt .= file_get_contents( __DIR__."/rewardedweb.js" );
		$this->mGptSlotDefines[] = $gpt;
	}

	public static function getCoppaValue() {
		global $wgTitle;
		$result = 'false';

		if ( self::isChildDirectedPage() ) {
			$result = 'true';

		}
		if ( ArticleTagList::hasTag( 'coppa_exclude', $wgTitle->getArticleID() ) ) {
			$result = 'admin';
		}

		return $result;
	}

	public static function isChildDirectedPage() {
		global $wgTitle;

		if ( !$wgTitle ) {
			return false;
		}

		if ( ArticleTagList::hasTag( 'coppa_exclude', $wgTitle->getArticleID() ) ) {
			return false;
		}

		if ( AlternateDomain::getAlternateDomainForCurrentPage() == "wikihow-fun.com" ) {
			return true;
		}

		if (!class_exists('Categoryhelper')) {
			return false;
		}
		$val = Categoryhelper::isTitleInCategory( $wgTitle, "Youth" );
		return $val;
	}

	public function getGPTDefine() {
		global $wgIsDevServer;
		if ( !$this->isDFPOkForSetup() ) {
			return '';
		}

		$dfpKeyVals = $this->getDFPKeyValsJSON();
		$gpt = "var gptAdSlots = [];\n";
		$gpt .= "var dfpKeyVals = $dfpKeyVals;\n";
		$gpt .= "var googletag = googletag || {};\n";
		$gpt .= "googletag.cmd = googletag.cmd || [];\n";
		$gpt .= "var gptRequested = gptRequested || false;\n";
		$gpt .= "function defineGPTSlots() {\n";
		// TODO in the future we can possibly define the GPT slot in js along with the new BodyAd call
		$gpt .= implode( $this->mGptSlotDefines );
		if ( self::isChildDirectedPage() ) {
			$gpt .= "googletag.pubads().setTagForChildDirectedTreatment(1);\n";
		}
		if ( !$this->isPrebidPage() ) {
			$gpt .= "googletag.pubads().enableSingleRequest();\n";
		}
		$gpt .= "googletag.pubads().disableInitialLoad();\n";
		if ( !$wgIsDevServer ) {
			$gpt .= "googletag.pubads().collapseEmptyDivs();\n";
		}
		$gpt .= "googletag.enableServices();\n";

		$gpt .= "}\n";
		$result = Html::inlineScript( $gpt );
		return $result;
	}

	public function showBlockthroughJs() {
		global $wgRequest, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return false;
		}

		if ( $wgRequest->getInt( 'blockthrough' )  == 1 ) {
			return true;
		}

		if ( Misc::isFastRenderTest() ) {
			return false;
		}

		if ( $this->mPageId == 400630 ) {
			return true;
		}

		$bucketId = intval( $this->mBucketId );
		$testBuckets = [4, 5, 6, 7, 8, 9, 10, 11];
		if ( in_array( $bucketId, $testBuckets ) ) {
			return true;
		}

		return false;
	}

	public function showNewAdsJs() {
		global $wgRequest, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return false;
		}

		if ( intval( $this->mBucketId ) >= 22 ) {
			return true;
		}
		if ( intval( $this->mBucketId ) == 18 ) {
			return true;
		}
		if ( intval( $this->mBucketId ) == 19 ) {
			return true;
		}

		return false;
	}

	public function isPrebidPage() {
		global $wgRequest, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return false;
		}

		if ( $wgRequest->getInt( 'prebid' )  == 1 ) {
			return true;
		}

		if ( intval( $this->mBucketId ) >= 24 ) {
			return true;
		}
		if ( intval( $this->mBucketId ) == 22 ) {
			return true;
		}
		if ( intval( $this->mBucketId ) == 19 ) {
			return true;
		}

		return false;
	}
	public function isPrebidPageNoBidCachingPage() {

		global $wgRequest, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return false;
		}

		if ( intval( $this->mBucketId ) >= 24 ) {
			return true;
		}
		if ( intval( $this->mBucketId ) == 19 ) {
			return true;
		}

		return false;
	}

	public function enableRewardedWebDefault() {
		global $wgRequest, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return false;
		}

		if ( $wgRequest->getInt( 'rw' )  == 1 ) {
			return true;
		}

		return false;
	}
	public function enableRewardedWebExample() {
		global $wgRequest, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return false;
		}

		if ( $wgRequest->getInt( 'rw' )  == 2 ) {
			return true;
		}

		return false;
	}
}

class DefaultMainPageAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 6166713376,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
		);
	}
}

class DefaultCategoryPageAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 7643446578,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
		);
	}
}

class DefaultAdCreatorKaiOS extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'kaiads',
				'publisher' => 'b9b87d46-5c86-4eb5-8465-84856ce26767',
				'width' => 240,
				'height' => 264,
				'smallslot' => 'intro_ad_small',
				'smallheight' => 264,
				'smallwidth' => 240,
				'class' => [],
				'type' => 'intro',
				'small' => 1,
			),
		);
	}

	public function getBodyAd( $type ) {
		$ad = $this->getNewAd( $type );

		if ( !isset( $ad->setupData ) ) {
			return null;
		}

		// get the inner ad
		$innerClass = $ad->setupData['innerclass'] ?:'';

		$attributes = array(
			'id' => $ad->mTargetId,
			'class' => $innerClass,
		);
		$innerHtml = '';

		if ( !$this->isAdOkForDomain( $ad ) ) {
			return;
		}

		$innerAdHtml = Html::rawElement( 'div', $attributes, $innerHtml );

		// now the wrapper
		$attributes = array(
			'class' => array( 'wh_ad_inner' ),
		);

		// all the settings for the ad come from the adSetupData
		foreach ( $ad->setupData as $key => $val ) {
			if ( $key == 'class' ) {
				foreach ( $val as $classVal ) {
					$attributes['class'][] = $classVal;
				}
			} elseif ( $key == 'containerheight' ) {
				$attributes['style'] = "height:{$val}px";
			} else {
				if ( is_array( $val ) ) {
					$val = implode(' ', $val);
				}
				$adKey = 'data-'.$key;
				$attributes[$adKey] = $val;
			}
		}

		$html = Html::rawElement( 'div', $attributes, $innerAdHtml );

		$ad->mHtml = $html;

		return $ad;
	}
}

class DefaultAdCreator extends AdCreator {
	public function __construct( $bucket = null ) {
		parent::__construct();
		if ( $bucket ) {
			$this->mBucketId = $bucket;
		}

		if ( ArticleTagList::hasTag( 'amp_disabled_pages', $this->mPageId ) ) {
			$this->mMobileAdsenseChannels[] = 8411928010;
		} else {
			$this->mMobileAdsenseChannels[] = 7928712280;
			// this group of pages have adsense on AMP, so we want to put a special channel to measure it
			// and we will put a corresponding channel on the adsense ads
			if ( $pageId % 100 < 10 ) {
				$this->mMobileAdsenseChannels[] = 9252820051;
			}
		}
		if ( intval( $this->mBucketId ) == 2 ) {
			$this->mMobileAdsenseChannels[] = 8177814015;
		} else {
			$this->mMobileAdsenseChannels[] = 6429618073;
		}

		if ( $this->isDFPSmallTest() ) {
			$this->mMobileAdsenseChannels[] = 7551128051;
		} else {
			$this->mMobileAdsenseChannels[] = 8375811488;
		}

		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 7672188889,
				'width' => 728,
				'height' => 120,
				'smallslot' => 8943394577,
				'smallheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
				'type' => 'intro',
				'small' => 1,
				'medium' => 1,
				'large' => 1,
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/engl/engl_gam_lgm_meth1',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 4313551892,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
				'medium' => 1,
				'large' => 1,
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 5490902193,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/engl/engl_gam_lgm_rght2',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'viewablerefresh' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 4177820525,
				'maxsteps' => 3,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'quiz' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/engl/engl_gam_lgm_quizz',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['hidden'],
				'type' => 'quiz',
				'medium' => 1,
				'large' => 1,
			),
			'related' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/engl/engl_gam_lgm_relat',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'qa' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/engl/engl_gam_lgm_qanda',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'slot' => 7710650179,
				'width' => 728,
				'height' => 90,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'method',
				'small' => 1,
				'medium' => 1,
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'slot' => 3648874275,
				'width' => 728,
				'height' => 90,
				'smallslot' => 9047782573,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'related',
				'small' => 1,
				'medium' => 1,
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'smallslot' => 3859396687,
				'smallheight' => 250,
				'type' => 'middlerelated',
				'small' => 1,
			),
			'mobileqa' => array(
				'service' => 'adsense',
				'slot' => 4167749029,
				'width' => 728,
				'height' => 90,
				'smallslot' => 1240030252,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'qa',
				'small' => 1,
				'medium' => 1,
			),
			'tips' => array(
				'service' => 'adsense',
				'smallslot' => 8787347780,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'tips',
				'small' => 1,
			),
			'warnings' => array(
				'service' => 'adsense',
				'smallslot' => 3674621907,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'warnings',
				'small' => 1,
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'smallslot' => 3788982605,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'pagebottom',
				'small' => 1,
			),
		);

		// dfp on mobile
		if ( $this->isDFPSmallTest() ) {
			$this->mAdSetupData = array(
				'intro' => array(
					'service' => 'adsense',
					'instantload' => 1,
					'slot' => 7672188889,
					'width' => 728,
					'height' => 120,
					'smallheight' => 120,
					'class' => ['ad_label', 'ad_label_dollar'],
					'type' => 'intro',
					'small' => 1,
					'medium' => 1,
					'large' => 1,
				),
				'method' => array(
					'service' => 'dfp',
					'adUnitPath' => '/10095428/engl/engl_gam_lgm_meth1',
					'size' => '[728, 90]',
					'apsLoad' => true,
					'aps-timeout' => 2000,
					'width' => 728,
					'height' => 90,
					'large' => 1,
				),
				'toc' => array(
					'service' => 'adsense',
					'slot' => 4313551892,
					'width' => 728,
					'height' => 90,
					'type' => 'toc',
					'medium' => 1,
					'large' => 1,
				),
				'rightrail0' => array(
					'service' => 'adsense',
					'slot' => 5490902193,
					'instantload' => 0,
					'width' => 300,
					'height' => 600,
					'containerheight' => 2000,
					'class' => ['rr_container'],
					'innerclass' => ['ad_label', 'ad_label_dollar'],
					'type' => 'rightrail',
					'large' => 1,
				),
				'rightrail1' => array(
					'service' => 'dfp',
					'adUnitPath' => '/10095428/engl/engl_gam_lgm_rght2',
					'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
					'apsLoad' => true,
					'refreshable' => 1,
					'viewablerefresh' => 1,
					'first-refresh-time' => 30000,
					'refresh-time' => 28000,
					'aps-timeout' => 800,
					'width' => 300,
					'height' => 600,
					'containerheight' => 3300,
					'class' => ['rr_container'],
					'innerclass' => ['ad_label', 'ad_label_dollar'],
					'type' => 'rightrail',
					'large' => 1,
				),
				'scrollto' => array(
					'service' => 'adsense',
					'type' => 'scrollto',
					'slot' => 4177820525,
					'maxsteps' => 3,
					'maxnonsteps' => 0,
					'width' => 728,
					'height' => 90,
					'large' => 1,
				),
				'quiz' => array(
					'service' => 'dfp',
					'adUnitPath' => '/10095428/engl/engl_gam_lgm_quizz',
					'size' => '[728, 90]',
					'apsLoad' => true,
					'aps-timeout' => 2000,
					'width' => 728,
					'height' => 90,
					'class' => ['hidden'],
					'type' => 'quiz',
					'medium' => 1,
					'large' => 1,
				),
				'related' => array(
					'service' => 'dfp',
					'adUnitPath' => '/10095428/engl/engl_gam_lgm_relat',
					'size' => '[728, 90]',
					'apsLoad' => true,
					'aps-timeout' => 2000,
					'width' => 728,
					'height' => 90,
					'large' => 1,
				),
				'qa' => array(
					'service' => 'dfp',
					'adUnitPath' => '/10095428/engl/engl_gam_lgm_qanda',
					'size' => '[728, 90]',
					'apsLoad' => true,
					'aps-timeout' => 2000,
					'width' => 728,
					'height' => 90,
					'large' => 1,
				),
				'mobilemethod' => array(
					'service' => 'adsense',
					'slot' => 7710650179,
					'width' => 728,
					'height' => 90,
					'smallheight' => 250,
					'smalllabel' => 1,
					'type' => 'method',
					'small' => 1,
					'medium' => 1,
				),
				'mobilerelated' => array(
					'service' => 'adsense',
					'slot' => 3648874275,
					'width' => 728,
					'height' => 90,
					'smallslot' => 9047782573,
					'smallheight' => 250,
					'smalllabel' => 1,
					'type' => 'related',
					'small' => 1,
					'medium' => 1,
				),
				'middlerelated' => array(
					'service' => 'adsense',
					'smallslot' => 3859396687,
					'smallheight' => 250,
					'type' => 'middlerelated',
					'small' => 1,
				),
				'mobileqa' => array(
					'service' => 'adsense',
					'slot' => 4167749029,
					'width' => 728,
					'height' => 90,
					'smallslot' => 1240030252,
					'smallheight' => 250,
					'smalllabel' => 1,
					'type' => 'qa',
					'small' => 1,
					'medium' => 1,
					'smallservice' => 'dfp',
					'class' => ['dfp_small'],
					'innerclass' => ['dfp_small_inner'],
					'adUnitPath' => '/10095428/engl/engl_gam_sma_qanda',
					'size' => '[300, 250]',
					'apsLoad' => true,
				),
				'tips' => array(
					'service' => 'adsense',
					'smallslot' => 8787347780,
					'smallheight' => 250,
					'smalllabel' => 1,
					'type' => 'tips',
					'small' => 1,
					'smallservice' => 'dfp',
					'class' => ['dfp_small'],
					'innerclass' => ['dfp_small_inner'],
					'adUnitPath' => '/10095428/engl/engl_gam_sma_tipps',
					'size' => '[300, 250]',
					'apsLoad' => true,
				),
				'warnings' => array(
					'service' => 'adsense',
					'smallslot' => 3674621907,
					'smallheight' => 250,
					'smalllabel' => 1,
					'type' => 'warnings',
					'small' => 1,
					'smallservice' => 'dfp',
					'class' => ['dfp_small'],
					'innerclass' => ['dfp_small_inner'],
					'adUnitPath' => '/10095428/engl/engl_gam_sma_warns',
					'size' => '[300, 250]',
					'apsLoad' => true,
				),
				'pagebottom' => array(
					'service' => 'adsense',
					'smallslot' => 3788982605,
					'smallheight' => 250,
					'smalllabel' => 1,
					'type' => 'pagebottom',
					'small' => 1,
				),
			);
		}

		if ( $this->isPrebidPage() ) {
			$this->mAdSetupData = array(
				'intro' => array(
					'service' => 'adsense',
					'instantload' => 1,
					'slot' => 7672188889,
					'width' => 728,
					'height' => 120,
					'smallheight' => 120,
					'class' => ['ad_label', 'ad_label_dollar'],
					'type' => 'intro',
					'small' => 1,
					'medium' => 1,
					'large' => 1,
				),
				'rightrail0' => array(
					'service' => 'adsense',
					'slot' => 5490902193,
					'instantload' => 0,
					'width' => 300,
					'height' => 600,
					'containerheight' => 2000,
					'class' => ['rr_container'],
					'innerclass' => ['ad_label', 'ad_label_dollar'],
					'type' => 'rightrail',
					'large' => 1,
				),
				'rightrail1' => array(
					'service' => 'dfp',
					'adUnitPath' => '/10095428/engl/engl_gam_lgm_rght2',
					'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
					'prebidLoad' => true,
					'bidcaching' => true,
					'apsLoad' => true,
					'refreshable' => 1,
					'viewablerefresh' => 1,
					'first-refresh-time' => 30000,
					'refresh-time' => 28000,
					'aps-timeout' => 800,
					'width' => 300,
					'height' => 600,
					'containerheight' => 3300,
					'class' => ['rr_container'],
					'innerclass' => ['ad_label', 'ad_label_dollar'],
					'type' => 'rightrail',
					'large' => 1,
				),
				'scrollto' => array(
					'service' => 'dfp',
					'type' => 'scrollto',
					'adUnitPath' => '/10095428/engl/engl_gam_all_scrol',
					'size' => '[728, 90]',
					'prebidLoad' => true,
					'bidcaching' => false,
					'apsLoad' => true,
					'aps-timeout' => 800,
					'maxsteps' => 6,
					'maxnonsteps' => 3,
					'width' => 728,
					'height' => 90,
					'large' => 1,
				),
			);
		}

		if ( $this->showNewAdsJs() ) {
			if ( intval( $this->mBucketId ) == 24 || intval( $this->mBucketId ) == 19 ) {
				$this->mAdSetupData['intro']['channels'] = [6132528505];
				$this->mAdSetupData['rightrail0']['channels'] = [6132528505];
			}
			if ( intval( $this->mBucketId ) == 23 || intval( $this->mBucketId ) == 18 ) {
				$this->mAdSetupData['intro']['channels'] = [1906768069];
				$this->mAdSetupData['toc']['channels'] = [1906768069];
				$this->mAdSetupData['rightrail0']['channels'] = [1906768069];
				$this->mAdSetupData['scrollto']['channels'] = [1906768069];
				$this->mAdSetupData['related']['channels'] = [1906768069];
				$this->mAdSetupData['qa']['channels'] = [1906768069];
			}
		}

		if ( $this->isPrebidPageNoBidCachingPage() ) {
			$this->mAdSetupData['rightrail1']['bidcaching'] = false;
		}

		if ( self::enableRewardedWebDefault() ) {
			$this->mAdSetupData['rewardedweb'] = array(
				'service' => 'dfp',
				'type' => 'rewardedweb',
				'adUnitPath' => '/10095428/rewarded/engl_gam_sma_rewrd',
			);
		}
		if ( self::enableRewardedWebExample() ) {
			$this->mAdSetupData['rewardedweb'] = array(
				'service' => 'dfp',
				'type' => 'rewardedweb',
				'adUnitPath' => '/6062/sanghan_rweb_ad_unit',
			);
		}
	}
}

// TODO test this
class DefaultDocViewerAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'docviewer0' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/Image_Ad_Sample_Page',
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 300,
				'height' => 600,
				'containerheight' => 600,
				'class' => ['rr_container'],
				'innerclass' => ['docviewerad', 'ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'medium' => 1,
				'large' => 1,
			),
			'docviewer1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/Image_Ad_Sample_728x90',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['docview_top', 'ad_label', 'ad_label_dollar'],
				'medium' => 1,
				'large' => 1,
			),
		);
	}

	public function isAdOkForDomain( $ad ) {
		return true;
    }

	protected function isDFPOkForSetup() {
		return true;
	}
}

class DefaultInternationalAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();

		$this->mAdsenseChannels[] = 4819709854;

		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 2583804979,
				'width' => 728,
				'height' => 120,
				'smallslot' => 2831688978,
				'smallheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
				'type' => 'intro',
				'small' => 1,
				'medium' => 1,
				'large' => 1,
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/intl/intl_gam_lgm_meth1',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 9337577313,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
				'medium' => 1,
				'large' => 1,
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 4060538172,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/intl/intl_gam_lgm_rght2',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'viewablerefresh' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 5411724845,
				'maxsteps' => 3,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'quiz' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/intl/intl_gam_lgm_quizz',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['hidden'],
				'type' => 'quiz',
				'medium' => 1,
				'large' => 1,
			),
			'related' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/intl/intl_gam_lgm_relat',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'medium' => 1,
				'large' => 1,
			),
			'qa' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/intl/intl_gam_lgm_qanda',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'medium' => 1,
				'large' => 1,
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'slot' => 6771527778,
				'width' => 728,
				'height' => 90,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'method',
				'small' => 1,
				'medium' => 1,
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'smallslot' => 9724994176,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'related',
				'small' => 1,
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'smallslot' => 7920322351,
				'smallheight' => 250,
				'type' => 'middlerelated',
				'small' => 1,
			),
			'mobileqa' => array(
				'service' => 'adsense',
				'smallslot' => 4517122485,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'qa',
				'small' => 1,
			),
			'tips' => array(
				'service' => 'adsense',
				'slot' => 6103187118,
				'width' => 728,
				'height' => 90,
				'smallslot' => 8125162876,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'tips',
				'small' => 1,
				'medium' => 1,
				'large' => 1,
			),
			'warnings' => array(
				'service' => 'adsense',
				'smallslot' => 4621387358,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'warnings',
				'small' => 1,
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'smallslot' => 3373074232,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'pagebottom',
				'small' => 1,
			),
		);
	}
}
class DefaultIntlCategoryListingAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();

		$this->mAdsenseChannels[] = 4819709854;

		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 4060538172,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 600,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
		);
	}
}

class DefaultInternationalAdCreatorAllAdsense extends AdCreator {
	public function __construct() {
		parent::__construct();
		// TODO do we need all these channels
		if ( WikihowToc::isNewArticle() ) {
			unset( $this->mAdSetupData['toc'] );
			$this->mAdsenseChannels[] = 1412197323;
		} else {
			$this->mAdsenseChannels[] = 7466415884;
		}

		$this->mAdsenseChannels[] = 2193546513;
		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 2583804979,
				'width' => 728,
				'height' => 120,
				'smallslot' => 2831688978,
				'smallheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
				'type' => 'intro',
				'small' => 1,
				'medium' => 1,
				'large' => 1,
			),
			'method' => array(
				'service' => 'adsense',
				'slot' => 3315713030,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 9337577313,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
				'medium' => 1,
				'large' => 1,
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 4060538172,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'rightrail1' => array(
				'service' => 'adsense',
				'slot' => 7854380386,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'rightrail2' => array(
				'service' => 'adsense',
				'slot' => 8731034705,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 5411724845,
				'maxsteps' => 3,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'quiz' => array(
				'service' => 'adsense',
				'slot' => 7964385233,
				'width' => 728,
				'height' => 90,
				'class' => ['hidden'],
				'type' => 'quiz',
				'medium' => 1,
				'large' => 1,
			),
			'related' => array(
				'service' => 'adsense',
				'slot' => 6448672327,
				'width' => 728,
				'height' => 90,
				'medium' => 1,
				'large' => 1,
			),
			'qa' => array(
				'service' => 'adsense',
				'slot' => 7334857519,
				'width' => 728,
				'height' => 90,
				'medium' => 1,
				'large' => 1,
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'slot' => 6771527778,
				'width' => 728,
				'height' => 90,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'method',
				'small' => 1,
				'medium' => 1,
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'smallslot' => 9724994176,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'related',
				'small' => 1,
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'smallslot' => 7920322351,
				'smallheight' => 250,
				'type' => 'middlerelated',
				'small' => 1,
			),
			'mobileqa' => array(
				'service' => 'adsense',
				'smallslot' => 4517122485,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'qa',
				'small' => 1,
			),
			'tips' => array(
				'service' => 'adsense',
				'slot' => 6103187118,
				'width' => 728,
				'height' => 90,
				'smallslot' => 8125162876,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'tips',
				'small' => 1,
				'medium' => 1,
				'large' => 1,
			),
			'warnings' => array(
				'service' => 'adsense',
				'smallslot' => 4621387358,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'warnings',
				'small' => 1,
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'smallslot' => 3373074232,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'pagebottom',
				'small' => 1,
			),
		);
	}

}

class DefaultSearchPageAdCreator extends AdCreator {
	public function __construct( $query = '' ) {
		parent::__construct();
		global $wgLanguageCode;

		if ( $wgLanguageCode == 'zh' ) {
			return;
		}

		if ( SearchAdExclusions::isExcluded( $query ) ) {
			return;
		}

		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 2504442946,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 630,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
				'channels' => [7445610171],
			),
		);
	}

	public function isAdOkForDomain( $ad ) {
		return true;
	}
}

class DefaultInternationalSearchPageAdCreator extends AdCreator {
	public function __construct($query = '') {
		parent::__construct();
		global $wgLanguageCode;

		if ( $wgLanguageCode == 'zh' ) {
			return;
		}

		if ( SearchAdExclusions::isExcluded( $query ) ) {
			return;
		}

		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 8601670711,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
		);
	}
}
