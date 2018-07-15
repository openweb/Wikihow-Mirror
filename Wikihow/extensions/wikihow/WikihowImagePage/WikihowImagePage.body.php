<?php
/**
 * Special handling for file description pages.
 *
 */

/**
 * Class for viewing NS_IMAGEl pages
 *
 * @
 */
class WikihowImagePage extends ImagePage {
	
	const LIGHTBOX_WIDTH = 900;
	const LIGHTBOX_HEIGHT = 900;

	public static function onArticleFromTitle( &$title, &$page ) {
		switch ($title->getNamespace()) {
			case NS_IMAGE:
			case NS_FILE:
				$page = new WikihowImagePage($title);
				break;
		}
		return true;
	}
	
	function renderAjax() {
		global $wgUser, $wgImageLimits, $wgRequest, $wgSquidMaxage;
		
		$out = $this->getContext()->getOutput();
		$out->setSquidMaxage($wgSquidMaxage);
		$out->setArticleBodyOnly(true);

		$image = RepoGroup::singleton()->findFile($this->mTitle);
		// get the id of the page that requested this so we can use it in the watermark
		$aid = $wgRequest->getVal('aid');

		if ( $aid ) {
			$params = array( 'width' => self::LIGHTBOX_WIDTH, 'height' => self::LIGHTBOX_HEIGHT, 'mArticleID' => $aid );
			$thumb = $image->transform( $params );
			$url = $thumb->getUrl();
		} else {
			$url = $image->createThumb(self::LIGHTBOX_WIDTH, self::LIGHTBOX_HEIGHT);
		}
		$helper = new ImageHelper();
		$info = $helper->getImageInfoWidget($this, $this->mTitle, $this->getDisplayedFile());
		
		$newSize = $helper->calcResize($image->width, $image->height, self::LIGHTBOX_WIDTH, self::LIGHTBOX_HEIGHT);
		
		if ($image && $image->exists()) {
				$out->addHtml("<div class='img-container animated fadeIn' style='height:" . $newSize['height'] . "px;'><img src='" . wfGetPad($url) . "'/></div>");
		}
		
		$out->addHtml('<div class="content-container animated fadeIn">');
		$out->addHtml($info);
		
		$out->addHtml('<div class="description">');
		$helper->showDescription($this->mTitle);
		$out->addHtml('</div>');
		
		if ($wgUser && !$wgUser->isAnon()) {
			$this->imageHistory();
		}
		$out->addHtml('</div>');

		$hash = explode('?', $wgRequest->getRequestURL());
		$out->addHtml(Html::inlineScript("window.location.hash='$hash[0]'"));
	}

	/*
	* Most of the logic for image pages exists in this method.  We're
	* overriding to put some extra bells and whistles
	*/
	function view() {
		global $wgShowEXIF, $wgRequest, $wgUser;
		
		// used by the lightbox effect on the article page
		if ($wgRequest->getVal('ajax') == 'true') {
			$this->renderAjax();
			return;
		}
		
		$out = $this->getContext()->getOutput();
		$sk = $this->getContext()->getSkin();
		$diff = $wgRequest->getVal( 'diff' );
		$diffOnly = $wgRequest->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );
		


		if ( $this->mTitle->getNamespace() != NS_IMAGE || ( isset( $diff ) && $diffOnly ) )
			return Article::view();	

		if ($wgShowEXIF && $this->getDisplayedFile()->exists()) {
			// FIXME: bad interface, see note on MediaHandler::formatMetadata().
			$formattedMetadata = $this->getDisplayedFile()->formatMetadata();
			$showmeta = $formattedMetadata !== false;
		} else {
			$showmeta = false;
		}

		$this->openShowImage();
		ImageHelper::showDescription($this->mTitle);

		$lastUser = $this->getDisplayedFile()->getUser();
		$userLink = Linker::link(Title::makeTitle(NS_USER, $lastUser), $lastUser);

		$out->addHTML("<div style='margin-bottom:20px'></div>");

		# Show shared description, if needed
		if ( $this->mExtraDescription ) {
			$fol = wfMessage( 'shareddescriptionfollows' )->plain();
			if( $fol != '-' && !wfMessage( 'shareddescriptionfollows' )->isBlank() ) {
				$out->addWikiText( $fol );
			}
			$out->addHTML( '<div id="shared-image-desc">' . $this->mExtraDescription . '</div>' );
		}
		$this->closeShowImage();
		$currentHTML = $out->getHTML();
		$out->clearHTML();
		Article::view();
		$articleContent = $out->getHTML();
		$out->clearHTML();
		$out->addHTML($currentHTML);

		$diffSeparator = "<h2>" . wfMessage('currentrev')->text() . "</h2>";
		$articleParts = explode($diffSeparator, $articleContent);
		if(count($articleParts) > 1){
			$out->addHTML($articleParts[0]);
		}
		$ih = new ImageHelper;
		$articles = $ih->getLinkedArticles($this->mTitle);
		
		if (ImageHelper::IMAGES_ON) {
			$ih->getConnectedImages($articles, $this->mTitle);
			ImageHelper::getRelatedWikiHows($this->mTitle, $sk);
		}
		$ih->addSideWidgets($this, $this->mTitle, $this->getDisplayedFile());

		# No need to display noarticletext, we use our own message, output in openShowImage()
		if ( $this->getID() ) {
			

		} else {
			# Just need to set the right headers
			$out->setArticleFlag( true );
			$out->setRobotpolicy( 'noindex,nofollow' );
			$out->setPageTitle( $this->mTitle->getPrefixedText() );
			//$this->viewUpdates();
		}

		if ($wgUser && !$wgUser->isAnon()) {
			$this->imageHistory();
		}

		//Taking out image ads on 1/12/15 at the request of google
		//ImageHelper::displayBottomAds();

		if ( $showmeta ) {
			$out->addHTML( Xml::element(
				'h2',
				array( 'id' => 'metadata' ),
				wfMessage( 'metadata' )->text() ) . "\n" );
			$out->addWikiText( $this->makeMetadataTable( $formattedMetadata ) );
			$out->addModules( array( 'mediawiki.action.view.metadata' ) );
		}
	}
		
		
		/*
		*  We're not interested in displaying this so just return an empty string in the 
		* case where writeIt is false
		*/
		function uploadLinksBox($writeIt = true) { 
			if (!$writeIt) {
				return "";	
			}
		}

		/*
		*  We'll use this in place of the uploadLinksBox in file history
		*/
		 function uploadLinksMessage($writeIt = true) {
				global $wgUser, $wgOut, $wgTitle;

				if( !$this->getDisplayedFile()->isLocal() )
						return;

				$html = '<br /><ul>';

				// wikitext message
				$html .= '<li>' . wfMessage('image_instructions', $wgTitle->getFullText())->text() . '</li></ul>';

				if ($writeIt) {
						$wgOut->addHtml($html);
				}
				else {
						return $html;
				}
		}
	

}

/*
* JRS extend ImageHistoryList so we can override display functions
* for image page history
*/
class WikihowImageHistoryList extends ImageHistoryList {

	function __construct( $imagePage ) {
		parent::__construct( $imagePage);
		$this->showThumb = false;
	}

	public function beginImageHistoryList($navLink = '') {
		 global $wgOut;
				$s = '<div class="minor_section">' . Xml::element( 'h2', array( 'id' => 'filehistory' ), wfMessage( 'filehist' )->text() )
						. '<div class="wh_block">'. $wgOut->parse( wfMessage( 'filehist-help' )->plain() )
						. Xml::openElement( 'table', array( 'class' => 'filehistory history_table' ) ) . "\n";
				return $s;
	}

	public function endImageHistoryList($navLink = '') {
		 return "</table>" . $this->imagePage->uploadLinksMessage(false) . "</div></div>\n";
	}

	public function onImagePageFileHistoryLine( $imagepage, $file, $line, $css) {
		return true;

	}
}
