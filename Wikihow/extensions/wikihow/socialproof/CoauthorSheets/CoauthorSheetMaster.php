<?php

class CoauthorSheetMaster extends CoauthorSheet {

	public static function getSheetId() {
		global $wgIsDevServer;
		return $wgIsDevServer ? '1f9e8jFSreExVIChrX-M_Ihh3CV-MD2P1VOQcCsVFdKY'
			: '19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I';
	}

	/**
	 * Imports the 'Master Expert Verified' sheet into the DB.
	 * Called by MasterExpertSheetUpdate via the AdminSocialProof special page.
	 */
	public function doImport(): array
	{
		$dbVerifiers = self::getVerifiersFromDB(); // TODO use VerifyData::getAllVerifierInfoFromDB()
		$result = ['errors' => [], 'warnings' => [], 'imported' => []];

		$coauthors = self::fetchSheetCoauthors($dbVerifiers, $result);
		$blurbs = self::fetchSheetBlurbs($coauthors, $result);
		$articles = self::fetchSheetArticles($coauthors, $blurbs, $result);

		self::processCoauthorRemovals($coauthors, $blurbs, $articles, $result);

		if (!$result['errors']) {
			self::reportBlurbChanges($blurbs);
			VerifyData::replaceCoauthors('en', $coauthors);
			VerifyData::replaceBlurbs('en', $blurbs);
			VerifyData::replaceArticles('en', $articles);
			CoauthorSheetIntl::recalculateIntlArticles();
			// Schedule the maintenance for the Reverification tool. Use the Main-page title because we need a title
			// in order for the job to work properly
			$title = Title::newFromText('Main-Page');
			$job = Job::factory('ReverificationMaintenanceJob', $title);
			JobQueueGroup::singleton()->push($job);

			// Clear the cache on the Article Reviewers page so that any newly imported experts can show
			ArticleReviewers::clearCache();
		}

		return $result;
	}

	public static function getArticleSheets() {
		// [ name => ID ]
		return [
			'Expert' => 'expert',
			'Academic' => 'academic',
			'YouTube' => 'video',
			'Community' => 'community',
			'Video Team Verified' => 'videoverified',
			'Chef Verified' => 'chefverified',
		];
	}

	/**
	 * Import article coauthors from "Co-Author Lookup".
	 *
	 * @param  $dbCoauthors  Existing coauthors, so we can detect removals
	 * @param  &$result      To be populated with info/errors/warnings
	 *
	 * @return array         Valid coauthors in the sheet
	 */
	private static function fetchSheetCoauthors(array $dbCoauthors, array &$result): array
	{
		$coauthors = [];
		$names = [];
		$rowGenerator = GoogleSheets::getRowsAssoc(self::getSheetId(), 'Co-Author Lookup');

		foreach ($rowGenerator as $num => $row) {
			$rowInfo = self::makeRowInfoHtml($num, self::getSheetId(), 'coauthors');

			$coauthorIdStr = $row['Coauthor ID'];
			$coauthorName = trim( $row['People'] );
			$whUserName = trim( $row['Portal Username'] );
			$initials = $row['Initials'];
			$category = trim( $row['Category'] );
			$nameUrl = trim( $row['Name Link URL (Eliz only)'] );
			$imageUrl = $row['Approved Image Url (Eliz only)'];

			$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo);
			if ( $coauthorId && isset($coauthors[$coauthorId]) ) {
				// TODO report 1st occurrence too (nice to have)
				$result['errors'][] = "$rowInfo Duplicate coauthor ID: $coauthorIdStr";
			}

			$whUserId = 0;
			if ($whUserName) {
				$whUser = User::newFromName($whUserName);
				$whUserId = $whUser ? $whUser->getId() : 0;
			}

			if ( !$coauthorName ) {
				$result['errors'][] = "$rowInfo Empty coauthor name";
			} elseif ( mb_strlen($coauthorName) < 2 ) {
				$result['errors'][] = "$rowInfo Coauthor name too short: $coauthorName";
			} elseif ( mb_strlen($coauthorName) > 120 ) {
				$result['errors'][] = "$rowInfo Coauthor name too long: $coauthorName";
			} elseif ( isset($names[$coauthorName]) ) {
				$result['errors'][] = "$rowInfo Duplicate coauthor name: $coauthorName";
			}

			if ( !$initials ) {
				$result['errors'][] = "$rowInfo Empty initials";
			} elseif ( mb_strlen($initials) > 10 ) {
				$result['errors'][] = "$rowInfo Initials too long: $initials";
			}

			if ( !$category ) {
				$result['errors'][] = "$rowInfo Empty category";
			} elseif ( mb_strlen($category) < 3 ) {
				$result['errors'][] = "$rowInfo Category too short: $category";
			} elseif ( mb_strlen($category) > 120 ) {
				$result['errors'][] = "$rowInfo Category too long: $category";
			}

			if ( $nameUrl && !filter_var($nameUrl, FILTER_VALIDATE_URL) ) {
				$result['errors'][] = "$rowInfo Invalid Name Link URL: $nameUrl";
			}

			if ( $imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL) ) {
				$result['errors'][] = "$rowInfo Invalid Approved Image URL: $imageUrl";
			}

			if ($coauthorId) {
				$vd = VerifyData::newVerifier( $coauthorId, $coauthorName, '', '', $nameUrl,
					$category, $imageUrl, $initials, $whUserId, $whUserName );
				$coauthors[$coauthorId] = $vd;
				$names[$coauthorName] = true;
			}
		}

		$rowInfo = self::makeRowInfoHtml(0, self::getSheetId(), 'coauthors');
		foreach ($dbCoauthors as $vID => $vName) {
			if ( !isset($coauthors[$vID]) ) {
				$result['errors'][] = "$rowInfo Coauthor was removed from the sheet: id=$vID, name='$vName'";
			} elseif ( $coauthors[$vID]->category == 'categ_removed' ) {
				$result['warnings'][] = "$rowInfo Coauthor is flagged with 'categ_removed' and will go away: id=$vID, name='$vName'";
			}
		}

		$errMsg = $rowGenerator->getReturn();
		if ($errMsg) {
			$rowInfo = self::makeRowInfoHtml(0, self::getSheetId(), 'coauthors');
			$result['errors'][] = "$rowInfo $errMsg";
		}

		return $coauthors;
	}

	/**
	 * Import from the "Blurb Lookup" worksheet
	 *
	 * @param  &$coauthors  Verifiers in 'Co-Author Lookup', so we can detect mismatches
	 * @param  &$result     To be populated with info/errors/warnings
	 *
	 * @return array        Valid blurbs in the sheet
	 */
	private static function fetchSheetBlurbs(array &$coauthors, array &$result): array
	{
		$blurbs = [];
		$rowGenerator = GoogleSheets::getRowsAssoc(self::getSheetId(), 'Blurb Lookup');

		foreach ($rowGenerator as $num => $row) {
			$rowInfo = self::makeRowInfoHtml($num, self::getSheetId(), 'blurbs');

			$coauthorIdStr = $row['Coauthor ID'];
			$blurbId = trim( $row['Blurb ID'] );
			$byline = trim( $row['Byline'] );
			$blurb = trim( $row['Blurb'] );

			$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo, $coauthors);

			list($coauthorId2, $blurbNum) = self::parseBlurbId(
				$blurbId, $coauthorId, $result['errors'], $rowInfo);

			if ( $blurbNum && isset($blurbs[$blurbId]) ) {
				// TODO report 1st occurrence too (nice to have)
				$result['errors'][] = "$rowInfo Duplicate blurb ID: $blurbId";
			}

			if ( !$byline ) {
				$result['errors'][] = "$rowInfo Empty byline";
			} elseif ( mb_strlen($byline) < 2 ) {
				$result['errors'][] = "$rowInfo Byline too short: $byline";
			} elseif ( mb_strlen($byline) > 200 ) {
				$result['errors'][] = "$rowInfo Byline too long: $byline";
			}

			if ( !$blurb ) {
				$result['errors'][] = "$rowInfo Empty blurb";
			} elseif ( mb_strlen($blurb) < 5 ) {
				$result['errors'][] = "$rowInfo Blurb too short: $blurb";
			} elseif ( mb_strlen($blurb) > 1500 ) {
				$result['errors'][] = "$rowInfo Blurb too long: $blurb";
			}

			$blurbs[$blurbId] = CoauthorBlurb::newFromAll($blurbId, $coauthorId, $blurbNum, $byline, $blurb);

			// Set the default blurb
			if ( $blurbNum === 1 && isset($coauthors[$coauthorId]) ) {
				$coauthors[$coauthorId]->blurb = $byline;
				$coauthors[$coauthorId]->hoverBlurb = $blurb;
			}
		}

		$rowInfo = self::makeRowInfoHtml(0, self::getSheetId(), 'blurbs');
		foreach ($coauthors as $coauthor) {
			if (!$coauthor->blurb || !$coauthor->hoverBlurb) {
				$name = $coauthor->name;
				$id = $coauthor->verifierId;
				$result['errors'][] = "$rowInfo Missing default blurb for coauthor: $name (id=$id)";
			}
		}

		$errMsg = $rowGenerator->getReturn();
		if ($errMsg) {
			$rowInfo = self::makeRowInfoHtml(0, self::getSheetId(), 'blurbs');
			$result['errors'][] = "$rowInfo $errMsg";
		}

		return $blurbs;
	}

	/**
	 * Import verified articles from: "Expert", "Academic", "YouTube", "Community",
	 * "Video Team Verified", and "Chef Verified".
	 *
	 * @param  $coauthors  Verifiers in 'Co-Author Lookup', so we can detect mismatches
	 * @param  $blurbs     Blurbs in 'Blurbs', so we can detect mismatches
	 * @param  &$result    To be populated with info/errors/warnings
	 *
	 * @return array       Valid articles in the sheet
	 */
	private static function fetchSheetArticles(array $coauthors, array $blurbs, array &$result ): array {
		$allRows = []; // every row in every worksheet
		$aids = [];    // article IDs every worksheet: [ aid => count ] (to detect duplicates)
		$dups = [];

		foreach ( self::getArticleSheets() as $worksheetName => $worksheetId )
		{
			$rowGenerator = GoogleSheets::getRowsAssoc(self::getSheetId(), $worksheetName);

			foreach ($rowGenerator as $num => $row) {
				$skip = ( 1 === intval($row['dev (leave blank if not dev)']) );
				if ($skip) {
					continue;
				}
				$row['num'] = $num;
				$row['worksheetId'] = $worksheetId;
				$allRows[] = $row;
				$aid = (int) $row['ArticleID'];
				$aids[$aid] = 1 + ($aids[$aid] ?? 0);
			}

			$errMsg = $rowGenerator->getReturn();
			if ($errMsg) {
				$rowInfo = self::makeRowInfoHtml(0, self::getSheetId(), $worksheetId);
				$result['errors'][] = "$rowInfo $errMsg";
			}
		}

		$articles = [];
		$titles = self::newFromIDsAssoc( $aids );

		foreach( $allRows as $row ) {
			$worksheetId = $row['worksheetId'];
			$rowInfo = self::makeRowInfoHtml($row['num'], self::getSheetId(), $worksheetId);

			// Data validation

			$errors = [];

			$pageIdStr = $row['ArticleID'];
			$pageId = (int) $pageIdStr;
			$articleName = $row['Article Name'];

			// $pageId
			$title = $titles[$pageId];
			list($titleLink, $titleSpan) = self::getTitleLink($title);
			$rowInfo .= $titleSpan;
			if ( !trim($pageIdStr) ) {
				$errors[] = "$rowInfo Empty article ID";
			} elseif ( $pageId <= 0 ) {
				$errors[] = "$rowInfo Invalid article ID: $pageIdStr";
			} elseif ( $aids[$pageId] > 1 ) {
				$dups[$pageId][] = self::makeRowLink($row['num'], self::getSheetId(), $worksheetId);
			} elseif ( !$title ) {
				$errors[] = "$rowInfo Article ID not found in DB: $pageIdStr";
			} elseif ( !$title->inNamespace(NS_MAIN) ) {
				$ns = $title->getNamespace();
				$errors[] = "$rowInfo Not an article: $titleLink (id=$pageIdStr, namespace=$ns)";
			} elseif ( $title->isRedirect() ) {
				$result['warnings'][] = "$rowInfo Redirect: $titleLink (id=$pageIdStr)";
			}

			// $articleName
			$t2 = Misc::getTitleFromText( $articleName );
			if ( !trim($articleName) ) {
				$errors[] = "$rowInfo Empty article name";
			} elseif ( !$t2 || !$t2->exists() ) {
				$errors[] = "$rowInfo Article Name not found in DB: $articleName";
			} else if ( $title && $pageId != $t2->getArticleID() ) {
				$key2 = $t2->getDBkey();
				$id2 = $t2->getArticleID();
				$errors[] = "$rowInfo Mismatch: ArticleID is $pageIdStr, but the ID for '$key2' is $id2";
			}

			if ( !in_array($worksheetId, ['chefverified', 'videoverified']) ) {
				$coauthorIdStr = $row['Coauthor ID'];
				$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo, $coauthors);

				$blurbId = trim( $row['Blurb ID'] );
				list($coauthorId2, $blurbNum) = self::parseBlurbId(
					$blurbId, $coauthorId, $result['errors'], $rowInfo);

				if ( $blurbNum && !isset($blurbs[$blurbId]) ) {
					$errors[] = "$rowInfo Blurb ID not found in 'Blurbs': $blurbId";
				}
			}

			if ( $worksheetId != 'chefverified' ) {
				$date = trim( $row['Verified Date'] );
				if ( !$date ) { // TODO validate
					$errors[] = "$rowInfo Empty Verified Date";
				}
				$revisionLink = trim( $row['Revision Link'] );
				$revId = (int) self::getRevId( $revisionLink );
				if ( !trim($revisionLink) ) {
					$errors[] = "$rowInfo Empty Revision Link URL";
				} elseif ( !$revId ) {
					$errors[] = "$rowInfo Invalid Revision Link URL: $revisionLink";
				}
			}

			if ($errors) {
				$result['errors'] = array_merge($result['errors'], $errors);
				continue;
			}

			// Make a VerifyData object

			if ( $worksheetId == 'chefverified' ) { // this sheet has no verifier data
				$verifyData = VerifyData::newChefArticle( $worksheetId, $pageId );
			}
			elseif ( $worksheetId == 'videoverified' ) {
				$verifyData = VerifyData::newVideoTeamArticle( $worksheetId, $pageId, $revId, $date );
			}
			else {
				$primaryBlurb = $blurbs[$blurbId]->byline;
				$hoverBlurb = $blurbs[$blurbId]->blurb;

				$coauthorName = $coauthors[$coauthorId]->name;

				$verifyData = VerifyData::newArticle( $pageId, $coauthorId, $date, $coauthorName, $blurbId, $primaryBlurb,
					$hoverBlurb, $revId, $worksheetId );
			}
			$articles[$pageId][] = $verifyData;
			$result['imported'][] = [ $pageId => $verifyData ];
		}

		foreach ($dups as $aid => $links) {
			$title = $titles[$aid];
			list($titleLink, $titleSpan) = self::getTitleLink($title);

			$locations = implode(',<br>', $links);
			$result['errors'][] = "<span class='spa_location'>{$locations}</span> Duplicate Article ID: {$aid}{$titleSpan}";
		}

		return $articles;
	}

	/**
	 * Coauthors that have been flagged in the EN Master sheet with 'categ_removed'
	 * will be deleted from the EN and INTL DBs along with their blurbs,
	 * UNLESS there are Q&A answers or verified articles associated to the coauthor,
	 * in which case a warning will be shown and the coauthors will be kept in the DB.
	 */
	private static function processCoauthorRemovals(array &$coauthors, array &$blurbs,
													array &$articles, array &$result)
	{
		/**
		 * Coauthors in the master sheet that were flagged with "categ_removed"
		 *
		 * [ COAUTHOR_ID1 => [
		 *       'articles' => [AID1, AID2],   # articles in the sheet associated to the coauthor
		 *     'qa_answers' => [QAID1, QAID2], # Q&A answers in the DB associated to the coauthor
		 *             'vd' => VerifyData
		 *   ],
		 *   COAUTHOR_ID2 => ...
		 * ]
		 */
		$flaggedCoauthors = [];
		foreach ($coauthors as $cid => $coauthor) {
			if ( $coauthor->category == 'categ_removed' ) {
				$flaggedCoauthors[$cid] = [ 'articles'=>[], 'qa_answers'=>[], 'vd' => $coauthor ];
			}
		}

		// Find associated articles
		foreach ($articles as $aid => $article) {
			$cid = $article[0]->verifierId;
			if ( array_key_exists($cid, $flaggedCoauthors) ) {
				$flaggedCoauthors[$cid]['articles'][] = $aid;
			}
		}

		// Find associated Q&A answers
		$dbr = wfGetDB( DB_REPLICA );
		foreach ( array_keys($flaggedCoauthors) as $cid) {
			$rows = $dbr->select(QADB::TABLE_ARTICLES_QUESTIONS,
				[ 'qa_id', 'qa_article_id', 'qa_question_id' ],
				[ 'qa_verifier_id' => $cid ],
				__METHOD__
			);
			foreach ($rows as $row) {
				$flaggedCoauthors[$cid]['qa_answers'][] = (int)$row->qa_id;
			}
		}

		$coauthorsToRemove = []; // Coauthors that can be deleted from the DB

		// Report articles or Q&A answers that prevent coauthor removals
		foreach ($flaggedCoauthors as $cid => $info) {
			$doRemove = true;
			if ($info['articles']) {
				$doRemove = false;
				$result['warnings'][] = "Coauthor $cid cannot be removed because it has the following articles: " . implode(', ', $info['articles']);
			}
			if ($info['qa_answers']) {
				$doRemove = false;
				$result['warnings'][] = "Coauthor $cid cannot be removed because it has the following Q&A answers: " . implode(', ', $info['qa_answers']);
			}
			if ( $doRemove ) {
				$coauthorsToRemove[$cid] = true;
			}
		}

		$blurbsToRemove = []; // Blurbs that can be deleted from the DB
		foreach ($blurbs as $bid => $blurb) {
			if ( array_key_exists( $blurb->coauthorId, $coauthorsToRemove ) ) {
				$blurbsToRemove[$bid] = true;
			}
		}

		// Amend arrays to reflect removals. DB deletion happens via VerifyData::replace*()
		$coauthors = array_diff_key($coauthors, $coauthorsToRemove);
		$blurbs = array_diff_key($blurbs, $blurbsToRemove);
	}

	/**
	 * Send an email notification
	 */
	private static function reportBlurbChanges(array $newBlurbs)
	{
		global $wgIsDevServer;

		$oldBlurbs = VerifyData::getAllBlurbsFromDB();
		$added = array_diff_key($newBlurbs, $oldBlurbs);
		$removed = array_diff_key($oldBlurbs, $newBlurbs);
		$changed = [];
		foreach ($oldBlurbs as $blurbId => $old) {
			$new = $newBlurbs[$blurbId] ?? null;
			if ( $new && ($new->byline != $old->byline || $new->blurb != $old->blurb) ) {
				$changed[$blurbId] = $new;
			}
		}

		$from = new MailAddress('alerts@wikihow.com');
		$to = new MailAddress( $wgIsDevServer ? 'alberto@wikihow.com' : 'vanna@wikihow.com' );
		$subject = "Coauthor Blurb Updates";
		$body = '';
		if ($added)   { $body .= "New: "      . implode(', ', array_keys($added))   . "\n"; }
		if ($changed) { $body .= "Modified: " . implode(', ', array_keys($changed)) . "\n"; }
		if ($removed) { $body .= "Removed: "  . implode(', ', array_keys($removed)) . "\n"; }

		if ($body) {
			UserMailer::send($to, $from, $subject, rtrim($body));
		}
	}

	private static function getTitleLink($title): array {
		if ( !$title || !$title->exists() ) {
			return [ '', '' ];
		}
		$aid = $title->getArticleID();
		$link = Html::rawElement( 'a', ['href'=>$title->getCanonicalURL(), 'target'=>'_blank'], $title->getDBKey() );
		$span = "<span style='float:right;'>$link ($aid)</span>";
		return [ $link, $span ];
	}

	/**
	 * Make an associative array of titles from an array of IDs
	 *
	 * @param array $ids of Int Array of IDs
	 * @return Array of Titles with key of the id
	 */
	private static function newFromIDsAssoc( $ids ) {
		if ( !count( $ids ) ) {
			return array();
		}
		$dbr = wfGetDB( DB_REPLICA );

		$fields = array(
			'page_namespace', 'page_title', 'page_id',
			'page_len', 'page_is_redirect', 'page_latest',
		);

		$res = $dbr->select(
			'page',
			$fields,
			array( 'page_id' => array_keys($ids) ),
			__METHOD__
		);

		$titles = array();
		foreach ( $res as $row ) {
			$titles[$row->page_id] = Title::newFromRow( $row );
		}
		return $titles;
	}

	private static function getRevId( $revisionLink ) {
		$output = array();
		parse_str( $revisionLink, $output );
		return $output['oldid'];
	}

	// TODO remove this method
	private static function getVerifiersFromDB(): array {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(VerifyData::VERIFIER_TABLE, ['vi_id', 'vi_name']);
		$dbVerifiers = [];
		foreach ($res as $row) {
			$dbVerifiers[ (int) $row->vi_id ] = $row->vi_name;
		}
		return $dbVerifiers;
	}

}
