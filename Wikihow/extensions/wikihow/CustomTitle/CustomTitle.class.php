<?php
//AUTHOR'S NOTE: Renamed from TitleTests 6/2018

/*db schema:
CREATE TABLE custom_titles(
	ct_pageid int unsigned NOT NULL,
	ct_page varbinary(255) NOT NULL,
	ct_type int(2) unsigned NOT NULL,
	ct_custom blob,
	ct_custom_heading blob,
	ct_custom_note blob,
	ct_timestamp varbinary(14) NOT NULL DEFAULT '',
	PRIMARY KEY (ct_pageid)
);
-- added this column to help with debugging if title generation isn't happening properly
alter table custom_titles add column ct_timestamp varbinary(14) NOT NULL DEFAULT '';
-- added this column to add support for custom headings
alter table custom_titles add column ct_custom_heading blob;
*/


class CustomTitle {

	const TABLE = 'custom_titles';
	const TYPE_DEFAULT = -1;
	const TYPE_CUSTOM = 100;
	const TYPE_SITE_PREVIOUS = 101;
	const TYPE_AUTO_GENERATED = 102;

	const MAX_TITLE_LENGTH = 66;

	const WRM_CREATED_START_TIME = '20180919000000';

	var $title;
	var $row;
	var $cachekey;

	// Flag can be set to avoid using memcache altogether
	static $forceNoCache = false;

	// Constructor called by factory method
	protected function __construct($title, $row) {
		$this->title = $title;
		$this->row = $row;
	}

	private static function getCachekey(int $pageid): string {
		return !self::$forceNoCache ? wfMemcKey('customtitle', $pageid) : '';
	}

	// Create a new CustomTitle object using pageid
	public static function newFromTitle(Title $title) {
		global $wgMemc;

		if (!$title || !$title->exists()) {
			// cannot create class
			return null;
		}

		$pageid = $title->getArticleId();
		$namespace = $title->getNamespace();
		if ($namespace != NS_MAIN || $pageid <= 0) {
			return null;
		}

		$cachekey = self::getCachekey($pageid);
		$row = $cachekey ? $wgMemc->get($cachekey) : false;
		if (!is_array($row)) {
			$dbr = wfGetDB(DB_REPLICA);
			$row = $dbr->selectRow(
				self::TABLE,
				[ 'ct_type', 'ct_custom', 'ct_custom_heading', 'ct_custom_note' ],
				[ 'ct_pageid' => $pageid ],
				__METHOD__);
			$row = $row ? (array)$row : [];
			if ($cachekey) $wgMemc->set($cachekey, $row);
		}

		$obj = new CustomTitle($title, $row);
		return $obj;
	}

	public function getTitle(): string {
		$ct_type = isset($this->row['ct_type']) ? (int)$this->row['ct_type'] : 0;
		$ct_custom = isset($this->row['ct_custom']) ? $this->row['ct_custom'] : '';

		$titleText = self::genTitle($this->title, $ct_type, $ct_custom);

		// Hack for alt domains.  Do some preg_replace magic on (non-custom) titles that may contain "- wikiHow" at
		// the end and use.  We can't do this in the title generation process as it's possible the custom title could
		// be generated when a logged in user (most likely wikiHow staff or editors) views it after editing.
		// Also, for this reason, don't save it in the db.
		if (AlternateDomain::onAlternateDomain() && $ct_type != self::TYPE_CUSTOM) {
			$titleText = preg_replace(
				"/- wikiHow$/",
				"- wikiHow " .
				ucwords(AlternateDomain::getAlternateDomainClass(AlternateDomain::getAlternateDomainForCurrentPage())),
				$titleText
			);
		}

		return $titleText;
	}

	public function getDefaultTitle(): array {
		$wasEdited = $this->row['ct_type'] == self::TYPE_CUSTOM;
		$defaultPageTitle = self::genTitle($this->title, self::TYPE_DEFAULT);
		return [ $defaultPageTitle, $wasEdited ];
	}

	public function getOldTitle(): string {
		$isCustom = $this->row['ct_type'] == self::TYPE_CUSTOM;
		$typeNum = $isCustom ? self::TYPE_CUSTOM : self::TYPE_SITE_PREVIOUS;
		$oldPageTitle = self::genTitle($this->title, $typeNum, $this->row['ct_custom']);
		return $oldPageTitle;
	}

	public function getHeading() {
		global $wgLanguageCode;
		$headingText = $this->row['ct_custom_heading'];
		if ( empty( $headingText ) ) {
			$titleMsg = $wgLanguageCode == 'ja' ? 'howto_article_heading' : 'howto';
			$headingText = wfMessage( $titleMsg, $this->title->getText() )->text();
		}
		return $headingText;
	}

	public function getData() {
		return [
			'title' => $this->row['ct_custom'],
			'heading' => $this->row['ct_custom_heading'],
			'note' => $this->row['ct_custom_note']
		];
	}

	private static function getWikitext(Title $title): array {
		$wikitext = Wikitext::getWikitext($title);
		$stepsText = '';
		if ($wikitext) {
			list($stepsText, ) = Wikitext::getStepsSection($wikitext, true);
		}
		return [ $wikitext, $stepsText ];
	}

	private static function getTitleExtraInfo(string $wikitext, string $stepsText): array {
		$numSteps = Wikitext::countSteps($stepsText);
		$numPhotos = Wikitext::countImages($wikitext);
		$numVideos = Wikitext::countVideos($wikitext);

		// for the purpose of title info, we are counting videos as images
		// since we default to showing images with the option of showing video under them
		$numPhotos = (int)$numPhotos + (int)$numVideos;

		$showWithPictures = false;
		if ($numSteps >= 5 && $numSteps <= 25) {
			if ($numPhotos > ($numSteps / 2) || $numPhotos >= 6) {
				$showWithPictures = true;
			}
		} else {
			if ($numPhotos > ($numSteps / 2)) {
				$showWithPictures = true;
			}
		}

		return [ $numSteps, $showWithPictures ];
	}

	private static function makeTitleInner(string $howto, int $numSteps, bool $withPictures = false): string {
		$stepsText = self::makeTitleSteps($numSteps);
		$picsText = $withPictures ? wfMessage('custom_title_with_pictures')->text() : '';
		$ret = $howto . $stepsText . $picsText;
		return trim($ret);
	}

	private static function makeTitleWays(int $ways, string $titleTxt, bool $wrm_created): string {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			if ($wrm_created)
				$prefix = self::getWRMPrefix($ways, $titleTxt);
			else
				$prefix = $ways.' '.wfMessage('custom_title_ways')->text();

			$ret = $prefix.' '.$titleTxt;
		}
		else {
			if (wfMessage('title_ways', $ways, $titleTxt)->isBlank()) {
				$ret = $titleTxt;
			} else {
				$ret = wfMessage('title_ways', $ways, $titleTxt)->text();
			}
		}
		return trim($ret);
	}

	private static function makeTitleSteps(int $numSteps): string {
		if ($numSteps <= 0 || $numSteps > 15) {
			return '';
		}

		$lang = RequestContext::getMain()->getLanguage()->getCode();
		switch ($lang) {
			case 'ar':
				if ( $numSteps == 1 ) {
					$txt = wfMessage('custom_title_step_number_ar_1')->text();
				} elseif ( $numSteps == 2 ) {
					$txt = wfMessage('custom_title_step_number_ar_2')->text();
				} elseif ( $numSteps >= 3 && $numSteps <= 10 ) {
					$txt = wfMessage('custom_title_step_number_ar_3_10', $numSteps)->text();
				} else {
					$txt = wfMessage('custom_title_step_number_ar_many', $numSteps)->text();
				}
				break;

			case 'cs':
				if ( $numSteps == 1 ) {
					$txt = wfMessage('custom_title_step_number_cs_1', $numSteps)->text();
				} elseif ( $numSteps >= 1 && $numSteps <= 5 ) {
					$txt = wfMessage('custom_title_step_number_cs_2_5', $numSteps)->text();
				} else {
					$txt = wfMessage('custom_title_step_number_cs_many', $numSteps)->text();
				}
				break;

			default:
				$txt = wfMessage('custom_title_step_number', $numSteps)->text();
				break;
		}

		return trim($txt);
	}

	private static function genTitle(Title $title, int $type = 0, string $custom = ''): string {
		if (!empty($custom) && ($type == self::TYPE_CUSTOM || $type == self::TYPE_AUTO_GENERATED)) {
			//we already have it; use it
			$titleTxt = $custom;
		} elseif ($type == self::TYPE_SITE_PREVIOUS) {
			//legacy support
			list($wikitext, $stepsText) = self::getWikitext($title);
			list($numSteps, $withPictures) = self::getTitleExtraInfo($wikitext, $stepsText);

			$howto = wfMessage('howto', $title->getText())->text();
			$inner = self::makeTitleInner($howto, $numSteps, $withPictures);

			$titleTxt = wfMessage('pagetitle', $inner)->text();
		} else {
			$titleTxt = self::makeTitle($title);
		}

		return $titleTxt;
	}

	private static function makeTitle(Title $title): string {
		// MediaWiki:max_title_length is used for INTL
		$maxTitleLength = (int)wfMessage("max_title_length")->plain() ?: self::MAX_TITLE_LENGTH;

		list($wikitext, $stepsText) = self::getWikitext($title);

		$methods = Wikitext::countAltMethods($stepsText);
		$hasParts = MagicWord::get( 'parts' )->match($wikitext);

		$pageName = $title->getText();

		//different rules for WRM-created articles
		$wrm_created = self::isArticleWRMCreated($title);

		if ($methods >= 3 && !$hasParts) {
			$inner = self::makeTitleWays($methods, $pageName, $wrm_created);
			$titleText = wfMessage('pagetitle', $inner)->text();
			if (mb_strlen($titleText) > $maxTitleLength) {
				$titleText = $inner;
			}
		}
		else {
			if ($wrm_created && $hasParts)
				$howto = self::makeTitleWays(0, $pageName, $wrm_created);
			elseif ($wrm_created)
				$howto = self::makeTitleWays($methods, $pageName, $wrm_created);
			else
				$howto = wfMessage('howto', $pageName)->text();

			list($numSteps, $withPictures) = self::getTitleExtraInfo($wikitext, $stepsText);

			$inner = self::makeTitleInner($howto, $numSteps, $withPictures);
			$titleText = wfMessage('pagetitle', $inner)->text();

			// first, try articlename + metadata + wikihow
			if (mb_strlen($titleText) > $maxTitleLength) {
				// next, try articlename + metadata
				$titleText = $inner;

				if ($numSteps > 0 && mb_strlen($titleText) > $maxTitleLength) {
					// next, try articlename + steps
					$titleText = self::makeTitleInner($howto, $numSteps);
				}

				if (mb_strlen($titleText) > $maxTitleLength) {
					// next, try articlename + wikihow
					$titleText = wfMessage('pagetitle', $howto)->text();

					if (mb_strlen($titleText) > $maxTitleLength) {
						// next, set title just as articlename
						$titleText = $howto;

						if (mb_strlen($titleText) > $maxTitleLength) {
							//lastly, do the default "how to"
							$titleText = wfMessage('howto', $pageName)->text();
						}
					}
				}
			}
		}

		return $titleText;
	}

	// uses the title text to grab a custom prefix from a mw msg list
	private static function getWRMPrefix(int $ways, string $titleTxt): string {
		$message = $ways > 2 ? 'custom_title_ways_prefixes_big' : 'custom_title_ways_prefixes_tiny';
		$prefixes = explode(',', wfMessage($message)->text());

		$modulus = count($prefixes);
		if (empty($modulus)) return trim(wfMessage('howto','')->text());

		$crc32 = crc32($titleTxt); //number based on title text
		$crc32 = abs($crc32); //positive numbers only
		$key = $crc32 % $modulus;

		$prefix = $prefixes[$key];

		if ($ways <= 2) $ways = '';

		return trim($ways.' '.$prefix);
	}

	private static function isArticleWRMCreated(Title $title): bool {
		$page_id = !empty($title) && $title->exists() ? $title->getArticleId() : 0;
		if (empty($page_id)) return false;

		$first_edit_user = wfGetDB(DB_REPLICA)->selectField(
			'firstedit',
			'fe_user_text',
			[
				'fe_page' => $page_id,
				'fe_timestamp > ' . self::WRM_CREATED_START_TIME
			],
			__METHOD__
		);

		return !empty($first_edit_user) && $first_edit_user == 'WRM';
	}

	/**
	 * Adds a new record to the custom_titles db table.  Called by
	 * importTitleTests.php.
	 */
	public static function dbAddRecord(&$dbw, Title $title, int $type) {
		global $wgMemc;
		if (!$title || !$title->inNamespace(NS_MAIN)) {
			throw new Exception('CustomTitle: bad title for DB call');
		}
		$pageid = $title->getArticleId();
		$dbw->replace(self::TABLE, 'ct_pageid',
			[ 'ct_pageid' => $pageid,
			  'ct_page' => $title->getDBkey(),
			  'ct_type' => $type,
			  'ct_timestamp' => wfTimestampNow() ],
			__METHOD__);
		$cachekey = self::getCachekey($pageid);
		if ($cachekey) $wgMemc->delete($cachekey);
	}

	/**
	 * Adds or replaces the current title with a custom one specified by
	 * a string from the admin. Note: must be a main namespace title.
	 */
	public static function dbSetCustomTitle(&$dbw, Title $title, string $custom, string $custom_heading = '', string $custom_note = '',
		int $type = self::TYPE_CUSTOM)
	{
		global $wgMemc;
		if (!$title || !$title->inNamespace(NS_MAIN)) {
			throw new Exception('CustomTitle: bad title for DB call');
		}
		$pageid = $title->getArticleId();
		$dbw->replace(self::TABLE, 'ct_pageid',
			[ 'ct_pageid' => $pageid,
			  'ct_page' => $title->getDBkey(),
			  'ct_type' => $type,
			  'ct_custom' => $custom,
			  'ct_custom_heading' => $custom_heading,
			  'ct_custom_note' => $custom_note,
			  'ct_timestamp' => wfTimestampNow() ],
			__METHOD__);
		$cachekey = self::getCachekey($pageid);
		if ($cachekey) $wgMemc->delete($cachekey);
	}

	/**
	 * List all "custom-edited" titles in one go
	 */
	public static function dbListCustomTitles(&$dbr): array {
		$res = $dbr->select(self::TABLE,
			[ 'ct_pageid', 'ct_page', 'ct_custom', 'ct_custom_heading', 'ct_custom_note' ],
			[ 'ct_type' => self::TYPE_CUSTOM ],
			__METHOD__);
		$pages = [];
		foreach ($res as $row) {
			$pages[] = (array)$row;
		}
		return $pages;
	}

	/**
	 * Reset title to auto-generated
	 */
	public static function dbResetTitle($dbw, Title $title) {
		$titleTxt = CustomTitle::genTitle( $title );
		$note = wfMessage( 'custom_note_auto_gen' )->text();
		self::dbSetCustomTitle( $dbw, $title, $titleTxt, '', $note, CustomTitle::TYPE_AUTO_GENERATED );
	}

	/**
	 * Remove a title from the list
	 */
	public static function dbRemoveTitle($dbw, Title $title) {
		self::dbRemoveTitleID( $dbw, $title->getArticleId() );
	}

	public static function dbRemoveTitleID(&$dbw, int $pageid) {
		global $wgMemc;
		$dbw->delete(self::TABLE,
			[ 'ct_pageid' => $pageid ],
			__METHOD__);
		$cachekey = self::getCachekey($pageid);
		if ($cachekey) $wgMemc->delete($cachekey);
	}

	// Called from resetAllAutoGeneratedTitles.php, which is occasionally used
	public static function dbDeleteAllAutoGenerated() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete( self::TABLE,
			[ 'ct_type' => self::TYPE_AUTO_GENERATED ],
			__METHOD__ );
		return $dbw->affectedRows();
	}

	public static function recalculateCustomTitleOnPageSave(WikiPage $wikiPage, User $user, Content $content,
			string $summary, int $minor, $null1, $null2, int $flags)
	{
		$title = $wikiPage->getTitle();
		if ( $title->inNamespace( NS_MAIN ) && !$title->isRedirect() ) {
			$custom_title = CustomTitle::newFromTitle( $title );
			if (
				// Add new one
				empty( $custom_title->row ) ||
				// Update existing (auto-generated only, as to not overwrite a custom one)
				$custom_title->row['ct_type'] == self::TYPE_AUTO_GENERATED
			) {
				$dbw = wfGetDB( DB_MASTER );
				self::dbResetTitle( $dbw, $title );
			}
		}
	}

	public static function onTitleMoveComplete( Title $oldTitle, Title $newTitle ) {
		$dbw = wfGetDB( DB_MASTER );
		if ( $oldTitle ) {
			self::dbRemoveTitle( $dbw, $oldTitle );
		}
		if ( $newTitle && $newTitle->inNamespace( NS_MAIN ) && !$newTitle->isRedirect() ) {
			self::dbResetTitle($dbw, $newTitle);
		}
	}

	public static function onArticleDelete(WikiPage $wikiPage) {
		if ($wikiPage) {
			$title = $wikiPage->getTitle();
			if ($title) {
				self::dbRemoveTitle(wfGetDB(DB_MASTER), $title);
			}
		}
	}
}
