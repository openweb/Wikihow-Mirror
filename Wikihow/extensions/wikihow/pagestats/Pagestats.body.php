<?php

class PageStats extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('PageStats');
	}

	public function isMobileCapable() {
		return true;
	}

	public static function getTitusData($pageId) {
		$context = RequestContext::getMain();
		$lang = $context->getLanguage()->getCode();

		$dbr = wfGetDB(DB_REPLICA);
		$table = Misc::getLangDB('en') . '.titus_copy';
		$where = ['ti_page_id' => $pageId, 'ti_language_code' => $lang];
		$row = $dbr->selectRow($table, '*', $where);
		return $row ?? null;
	}

	public static function getRatingReasonData($pageId, $type, &$dbr) {
		if (!isset($val)) $val = new stdClass();
		$val->total = $dbr->selectField('rating_reason',
			"count(*)",
			array("ratr_item" => $pageId, "ratr_type" => $type),
			__METHOD__);
		return $val;
	}

	public static function getRatingData($pageId, $tableName, $tablePrefix, &$dbr) {
		global $wgMemc;

		$val = new stdClass();
		$val->total = 0;
		$yes = 0;

		$res = $dbr->select($tableName,
			"{$tablePrefix}_rating as rating",
			array("{$tablePrefix}_page" => $pageId, "{$tablePrefix}_isdeleted" => 0),
			__METHOD__);
		foreach ($res as $row) {
			$val->total++;
			if ($row->rating == 1)
				$yes++;
		}

		if ($val->total > 0) {
			$val->percentage = round($yes*1000/$val->total)/10;
		} else {
			$val->percentage = 0;
		}

		return $val;
	}

	private static function getFellowsTime($fellowEditTimestamp) {
		$context = RequestContext::getMain();
		$lang = $context->getLanguage();

		$d = false;
		if (!$fellowEditTimestamp) {
			return false;
		}

		$ts = wfTimestamp( TS_MW, strtotime($fellowEditTimestamp));
		$hourMinute = $lang->sprintfDate("H:i", $ts);
		if ($hourMinute == "00:00") {
			$d = $lang->sprintfDate("j F Y", $ts);
		} else {
			$d = $lang->timeanddate($ts);
		}
		$result = "<p>" . wfMessage('ps-fellow-time') . " $d&nbsp;&nbsp;</p>";
		return $result;
	}

	private function getPagestatData($pageId) {
		$t = Title::newFromID($pageId);
		$dbr = wfGetDB(DB_REPLICA);

		$html = "<h3 style='margin-bottom:5px'>Staff-only data</h3>";

		if ( SummaryEditTool::authorizedUser( $this->getUser() ) ) {
			$html =  SummaryEditTool::editCTAforArticlePage() . $html;
		}

		$error = null;
		$titusData = self::getTitusData($pageId);
		if (!$titusData) {
			$error = "No Titus data was found for article: $pageId";
			$html .= "<p>" . wfMessage('ps-error') . "</p>";
			$html .= "<hr style='margin:5px 0; '/>";
		} else {
			// pageview data
			$views30Day = $titusData->ti_30day_views_unique;
			$views30DayMobile = $titusData->ti_30day_views_unique_mobile;
			$html .= wfMessage( 'ps-pv-30day-unique', $views30Day )->text();
			$mobile30DayPercent = 0;
			if ( $views30Day > 0 ) {
				$mobile30DayPercent = round( 100 * $views30DayMobile / $views30Day );
			}

			$viewsDay = $titusData->ti_daily_views_unique;
			$viewsMobile = $titusData->ti_daily_views_unique_mobile;
			$html .= wfMessage( 'ps-pv-1day-unique', $viewsDay )->text();
			$mobilePercent = 0;
			if ( $viewsDay > 0 ) {
				$mobilePercent = round( 100 * $viewsMobile / $viewsDay );
			}

			$html .= "<p>{$titusData->ti_30day_views} " . wfMessage('ps-pv-30day') . "</p>";
			$html .= "<p>{$titusData->ti_daily_views} " . wfMessage('ps-pv-1day') . "</p>";

			$html .= "<hr style='margin:5px 0; '/>";
			$html .= wfMessage('ps-pv-30day-unique-mobile', $views30DayMobile, $mobile30DayPercent )->text();
			$html .= wfMessage('ps-pv-1day-unique-mobile', $viewsMobile, $mobilePercent )->text();

			// stu data
			$html .= "<hr style='margin:5px 0; '/>";
			$html .= "<p>" . wfMessage('ps-stu') . " {$titusData->ti_stu_10s_percentage_www}%&nbsp;&nbsp;{$titusData->ti_stu_3min_percentage_www}%&nbsp;&nbsp;{$titusData->ti_stu_10s_percentage_mobile}%</p>";
			$html .= "<p>" . wfMessage('ps-stu-views') . "{$titusData->ti_stu_views_www}&nbsp;&nbsp;{$titusData->ti_stu_views_mobile}</p>";
			if ($t) {
				$html .= "<p><a href='#' class='clearstu'>Clear Stu</a></p>";
			}

			// stu2 data
			$nb = '&nbsp;';
			$r = $titusData->ti_stu2_last_reset;
			if ($r && strlen($r) == 8) {
				$resetLine = "<i>last reset " . substr($r, 0, 4) . '/' . substr($r, 4, 2) . '/' . substr($r, 6, 2) . "</i>";
			} else {
				$resetLine = "";
			}
			$html .= "<hr style='margin:5px 0; '/>";
			$html .= "<p><b>Stu2</b> $nb$nb$nb$resetLine</p>";
			if ($titusData->ti_stu2_search_mobile) {
				$stu2Mb10sAc = sprintf( '%.1f', $titusData->ti_stu2_10s_active_mobile ) . "%";
				$stu2Mb3mAc = sprintf( '%.1f', $titusData->ti_stu2_3m_active_mobile ) . "%";
				$html .= "<p>mobile:$nb$stu2Mb10sAc$nb$stu2Mb3mAc$nb{$nb}views:{$titusData->ti_stu2_search_mobile}</p>";
			} else {
				$html .= "<p>mobile: <i>(no search views)</i></p>";
			}
			if ($titusData->ti_stu2_search_desktop) {
				$stu2Dt10sAc = sprintf( '%.1f', $titusData->ti_stu2_10s_active_desktop ) . "%";
				$stu2Dt3mAc = sprintf( '%.1f', $titusData->ti_stu2_3m_active_desktop ) . "%";
				$html .= "<p>desktop:$nb$stu2Dt10sAc$nb$stu2Dt3mAc$nb{$nb}views:{$titusData->ti_stu2_search_desktop}</p>";
			} else {
				$html .= "<p>desktop: <i>(no search views)</i></p>";
			}
			if ($titusData->ti_stu2_activity_count_mobile) {
				$mbAct = sprintf("%.1f%%$nb(%d)", $titusData->ti_stu2_activity_avg_mobile, $titusData->ti_stu2_activity_count_mobile);
			} else {
				$mbAct = "<i>(no activity)</i>";
			}
			if ($titusData->ti_stu2_activity_count_desktop) {
				$dtAct = sprintf("%.1f%%$nb(%d)", $titusData->ti_stu2_activity_avg_desktop, $titusData->ti_stu2_activity_count_desktop);
			} else {
				$dtAct = "<i>(no activity)</i>";
			}
			$html .= "<p>activity{$nb}mobile:{$mbAct}{$nb}dt:{$dtAct}</p>";
			$html .= "<p style='font-size:12px; font-weight:bold; padding-top:3px'>More info</p>";
			$html .= "<p>search views mobile:{$titusData->ti_stu2_search_mobile}{$nb}dt:{$titusData->ti_stu2_search_desktop}</p>";
			$html .= "<p>all views mobile:{$titusData->ti_stu2_all_mobile}{$nb}dt:{$titusData->ti_stu2_all_desktop}</p>";
			$html .= "<p>quick{$nb}bounces{$nb}amp:{$titusData->ti_stu2_amp}{$nb}mobile:{$titusData->ti_stu2_quickbounce_mobile}{$nb}dt:{$titusData->ti_stu2_quickbounce_desktop}</p>";

			// summary video data
			$hasSummaryVideo = $titusData->ti_summary_video;
			if ( $hasSummaryVideo ) {
				// $views = $titusData->ti_summary_video_views + $titusData->ti_summary_video_views_mobile;
				$plays = $titusData->ti_summary_video_play + $titusData->ti_summary_video_play_mobile;
				$html .= "<hr style='margin:5px 0; '/>";
				$html .= "<p>Summary Video Plays: {$plays}</p>";
			}
		}

		$haveBabelfishData = false;
		$languageCode = null;
		if ($titusData) {
			$languageCode = $titusData->ti_language_code;
			// search volume data
			$html .= "<hr style='margin:5px 0; '/>";
			$html .= "<p>Search volume: " . $titusData->ti_search_volume . " - " . $titusData->ti_search_volume_label . "</p>";
			// fellow data
			$html .= "<hr style='margin:5px 0; '/>";
			$html .= "<p>" . wfMessage('ps-fellow') . " ";
			$html .= $titusData->ti_last_fellow_edit ?:"";
			$html .= "&nbsp;&nbsp;</p>";
			$html .= self::getFellowsTime($titusData->ti_last_fellow_edit_timestamp) ?: "";
			$html .= self::getEditingStatus( $titusData->ti_editing_status );

			// babelfish rank
			$haveBabelfishData = true;
			$bfRank = $titusData->ti_babelfish_rank ?: "no data";
			$html .= "<hr style='margin:5px 0; '/>";
			$html .= "<p>" . wfMessage('ps-bfish') . ": {$bfRank}&nbsp;&nbsp;</p>";
			$html .= "<p>" . wfMessage('ps-methods') . ": {$titusData->ti_alt_methods}</p>";
		}

		// languages translated
		$lLinks = array();
		if ($languageCode) {
			try {
				$linksTo = TranslationLink::getLinksTo($languageCode, $pageId, true);
				foreach($linksTo as $link) {
					if ($link->fromLang == $languageCode) {
						$href = str_replace("'", "%27", $link->toURL);
						$lLinks[] = "<a href='".htmlspecialchars($href)."'>$link->toLang</a>";
					} else {
						$href = str_replace("'", "%27", $link->fromURL);
						$lLinks[] = "<a href='".htmlspecialchars($href)."'>". $link->fromLang ."</a>";
					}
				}
			} catch (DBQueryError $e) {
				$lLinks[] = "<p>".$e->getText()."</p>";
			}
		}

		// only print the line if we have not printed it above with babelfish data
		if (!$haveBabelfishData) {
			$html .= "<hr style='margin:5px 0;' />";
		}
		$html .= "<p>Translated: " . implode($lLinks, ',') . "</p>";

		// Sensitive Article Tagging

		if ($this->getLanguage()->getCode() == 'en') {
			$html .= "<hr style='margin:5px 0; '/>";
			$saw = new SensitiveArticle\SensitiveArticleWidget($pageId);
			$html .= '<div id="sensitive_article_widget">' . $saw->getHTML() . '</div>';
		}

		// Inbound links
		$target = SpecialPage::getTitleFor('Whatlinkshere', $t->getText());
		$target .= '?namespace=0&hideredirs=1';

		if (Misc::doResponsive( $this->getContext() )) {
			$target = WikihowMobileTools::getNonMobileSite().'/'.$target;
		}
		$anchor = ArticleStats::getInboundLinkCount($t);

		$link = HTML::rawElement('a', ['href' => $target], $anchor);

		$html .= "<hr style='margin:5px 0;' />";
		$html .= "<p>Inbound links: $link</p>";

		$html .= "<hr style='margin:5px 0; '/>";
		$html .= MotionToStatic::getMotionToStaticHtmlForPageStats();

		// Add Googlebot stats for page from botwatch_daily
		$html .= "<hr style='margin:5px 0;' />";
		$html .= "<p>Googlebot</p>";
		if ($titusData->ti_googlebot_7day_views_mobile) {
			$hits = round($titusData->ti_googlebot_7day_views_mobile / 7.0, 1);
			$ts = wfTimestamp(TS_UNIX, $titusData->ti_googlebot_7day_last_mobile);
			$last = self::timeElapsedString('@' . $ts);
			$html .= "<p>mobile: {$hits} hits/day (last: {$last})</p>";
		}
		if ($titusData->ti_googlebot_7day_views_amp) {
			$hits = round($titusData->ti_googlebot_7day_views_amp / 7.0, 1);
			$ts = wfTimestamp(TS_UNIX, $titusData->ti_googlebot_7day_last_amp);
			$last = self::timeElapsedString('@' . $ts);
			$html .= "<p>AMP: {$hits} hits/day (last: {$last})</p>";
		}
		if ($titusData->ti_googlebot_7day_views_desktop) {
			$hits = round($titusData->ti_googlebot_7day_views_desktop / 7.0, 1);
			$ts = wfTimestamp(TS_UNIX, $titusData->ti_googlebot_7day_last_desktop);
			$last = self::timeElapsedString('@' . $ts);
			$html .= "<p>desktop: {$hits} hits/day (last: {$last})</p>";
		}
		if (!$titusData->ti_googlebot_7day_views_mobile
			&& !$titusData->ti_googlebot_7day_views_amp
			&& !$titusData->ti_googlebot_7day_views_desktop
		) {
			$html .= "<p><i>No recorded data</i></p>";
		} else {
			$html .= "<p><i>times are relative to midnight</i></p>";
		}

		// Article id
		$html .= "<hr style='margin:5px 0;' />";
		$html .= "<p>Article Id: $pageId</p>";

		return ["body"=>$html, "error"=>$error];
	}

	// https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
	private static function timeElapsedString($datetime, $full = false) {
		$tz = new DateTimeZone('America/Los_Angeles');
		$now = new DateTime('midnight today', $tz);
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);

		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;

		$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}

		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' before' : 'midnight';
	}


	private static function getEditingStatus( $status ) {
		$statusLine = Html::element( 'p', array( 'id' => 'staff-editing-menu-status' ), 'Editing Status: ' . $status );
		$menuTitle = Html::element( 'p', array( 'id' => 'staff-editing-menu-title' ), 'Editing option:' );
		$options = '';
		$options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'editing' ), 'Request Editing' );
		$options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'stub' ), 'Send note to future editor' );
		$options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'removal' ), 'Request removal from Editfish' );
		$options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'stub' ), 'Request Stub (low quality/low PV/bad title)' );
		$options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'summaryvideo' ), 'Edit Quick Summary' );
		$menuContent = Html::rawElement( 'div', array( 'id'=> 'staff-editing-menu-content', 'class' => 'menu' ), $options );
		$textArea = Html::rawElement( 'textarea', array( 'id'=> 'sem-textarea', 'class' => 'sem-h', 'placeholder' => 'add any extra comments here' ) );

		$checkBox = Html::rawElement( 'input', array( 'id'=> 'sem-hp-box', 'type' => 'checkbox' ) );
		$checkBoxLabel = Html::rawElement( 'label', array(), "High Priority" );
		$checkBoxWrap = Html::rawElement( 'div', array( 'id' => 'sem-hp', 'class' => 'sem-h' ), $checkBox . $checkBoxLabel );

		$submit .= Html::rawElement( 'a', array( 'id' => 'staff-editing-menu-submit', 'class' => 'sem-h', 'href' => '#' ), 'Submit Editing Request' );
		$menuWrap = Html::rawElement( 'div', array( 'id' => 'staff-editing-menu' ), $menuTitle . $menuContent );
		return $statusLine . $menuWrap . $type . $textArea . $checkBoxWrap . $submit;
	}

	public static function getSampleStatData($sampleTitle) {
		$html = "";

		$dbr = wfGetDB(DB_REPLICA);

		$data = self::getRatingData($sampleTitle, 'ratesample', 'rats', $dbr);
		$html .= "<hr style='margin:5px 0;' />";
		$html .= "<p>Rating Accuracy: {$data->percentage}% of {$data->total} votes</p>";

		$cl = Title::newFromText('ClearRatings', NS_SPECIAL);
		$link = Linker::link($cl, 'Clear ratings', array(), array('type' => 'sample', 'target' => $sampleTitle));
		$html .= "<p>{$link}</p>";

		$data = self::getRatingReasonData($sampleTitle, 'sample', $dbr);
		$html .= "<hr style='margin:5px 0;' />";
		$html .= "<p>Rating Reasons: {$data->total}</p>";

		$cl = SpecialPage::getTitleFor( 'AdminRatingReasons');
		$link = Linker::link($cl, 'View rating reasons', array(), array('item' => $sampleTitle));
		$html .= "<p>{$link}</p>";

		$cl = SpecialPage::getTitleFor( 'AdminRemoveRatingReason', $sampleTitle);
		$link = Linker::link($cl, 'Clear rating reasons');
		$html .= "<p>{$link}</p>";

		return $html;
	}

	private static function addData(&$data) {
		$html = "";
		foreach($data as $key => $value) {
			$html .= "<tr><td style='font-weight:bold; padding-right:5px;'>" . $value . "</td><td>" . wfMessage("ps-" . $key) . "</td></tr>";
		}
		return $html;
	}

	public function execute($par) {
		$out = $this->getContext()->getOutput();
		$request = $this->getRequest();
		$action = $request->getVal('action');
		if ($action == 'ajaxstats') {
			$out->setArticleBodyOnly(true);
			$target = $request->getVal('target');

			$type = $request->getVal('type');
			if ($type == "article") {
				$title = !empty($target) ? Title::newFromURL($target) : null;
				if ($title && $title->exists()) {
					$result = self::getPagestatData($title->getArticleID());
					print json_encode($result);
				}
			} elseif ($type == "sample") {
				$title = !empty($target) ? Title::newFromText("sample/$target") : null;
				if ($title) {
					$result = array(
						'body' => self::getSampleStatData($target)
					);
					print json_encode($result);
				}
			}
		} elseif ( $request->wasPosted() && $action == 'motiontostatic' ) {
			$out->setArticleBodyOnly(true);
			$editResult = MotionToStatic::handlePageStatsPost( $request, $user );
			$result = array();
			if ($editResult == '') {
				$result = array( 'success' => true, 'message' => 'your edit was saved' );
			} else {
				$result = array( 'success' => false, 'message' => $editResult );
			}
			print json_encode($result);
			return;
		} elseif ( $request->wasPosted() && $action == 'editingoptions' ) {
			$out->setArticleBodyOnly(true);
			$textBox = $request->getVal( 'textbox' );
			$type = $request->getVal( 'type' );
			$highPriority = $request->getVal( 'highpriority' );
			if ( $highPriority == 'true' ) {
				$highPriority = 1;
			} else {
				$highPriority = 0;
			}
			$pageId = $request->getVal( 'pageid' );
			$title  = Title::newFromID( $pageId );
			if ( $title && $title->exists() ) {
				$title = $title->getFullURL();
			} else {
				$title = "unknown";
			}
			$isSummaryVideoFeedback = false;
			if ( $type == 'Edit In A Hurry' ) {
				$isSummaryVideoFeedback = true;
			}
			$file = $this->getSheetsFile( $isSummaryVideoFeedback );
			$sheet = $file->sheet('default');
			$userName = $this->getUser()->getName();
			$data = array(
				'submitter' => $userName,
				'time' => date('Y-m-d'),
				'option' => $type,
				'comment' => $textBox,
				'url' => $title,
				'pageid' => $pageId,
				'highpriority' => $highPriority,
			);
			if ( $isSummaryVideoFeedback ) {
				$data = array(
					'time' => date('Y-m-d'),
					'submitter' => $userName,
					'pageid' => $pageId,
					'url' => $title,
					'comments' => $textBox,
					'new' => $highPriority,
				);
			}
			$sheet->insert( $data );
			return;
		}
	}

	/**
	 * @return Google_Spreadsheet_File
	 */
	private function getSheetsFile( $isSummaryVideoFeedback = false ): Google_Spreadsheet_File {
		global $wgIsProduction;

		$keys = (Object)[
			'client_email' => WH_GOOGLE_SERVICE_APP_EMAIL,
			'private_key' => file_get_contents(WH_GOOGLE_DOCS_P12_PATH)
		];
		$client = Google_Spreadsheet::getClient($keys);

		// Set the curl timeout within the raw google client.  Had to do it this way because the google client
		// is a private member within the Google_Spreadsheet_Client
		$rawClient = function(Google_Spreadsheet_Client $client) {
			return $client->client;
		};
		$rawClient = Closure::bind($rawClient, null, $client);
		$timeoutLength = 600;
		$configOptions = [
			CURLOPT_CONNECTTIMEOUT => $timeoutLength,
			CURLOPT_TIMEOUT => $timeoutLength
		];
		$rawClient($client)->setClassConfig('Google_IO_Curl', 'options', $configOptions);

		if ($wgIsProduction) {
			if ( $isSummaryVideoFeedback ) {
				$fileId = '1E86B9G_Za-FSicM14vsMTwjCNHIScZgPsAbfq7HYQOU';
			} else {
				$fileId = '11BpgghgRSFuRfylWoViEhQnn8ib-jCXGrNE7qkGchJk';
			}
		} else {
			if ( $isSummaryVideoFeedback ) {
				$fileId = '1xpmYq7euPEEcweyTWkljDmloaT7scM0WDVn4vrIIh3M';
			} else {
				$fileId = '1sMPfAjcG2zCj2c-m3o57QIQpnG19a8Z1SgohR0FP6GA';
			}
		}
		$file = $client->file($fileId);

		return $file;
	}

	public static function getJSsnippet() {
		$out = RequestContext::getMain()->getOutput();
		$out->addModules( 'ext.wikihow.pagestats' );
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$title = $out->getTitle();
		$user = $out->getUser();

		if ( $title &&
			$title->inNamespace(NS_MAIN) &&
			!$title->isMainPage() &&
			Misc::isUserInGroups($user, ['staff', 'staff_widget', 'editor_team']) &&
			$user->isLoggedIn() )
		{
			$out->addModules( 'ext.wikihow.pagestats' );
		}
	}

}
