<?php

/**
 * Updates the Master Expert Verified spreadsheet with the latest reverifications.
 * Spreadsheet ID: CoauthorSheetMaster::getSheetId()
 */
class ReverificationSpreadsheetUpdater {

	/**
	 * @var ReverificationData[]
	 */
	var $exportData = [];
	var $emailLog = [];
	var $totalUpdateCount = 0;
	var $totalUpdated = 0;
	var $verifierCount = [];
	var $totalSkipped = 0;
	var $verifierSkipCount = [];
	var $startTime = null;
	var $sheetNames = ['Expert', 'Academic'];
	// var $sheetNames = ['Academic'];
	var $errors = [];
	const TIMEOUT_LENGTH = 600;
	const MAX_PER_DAY = 15;

	public function update() {
		$this->startTime = wfTimestampNow();

		$this->getExportData();
		$this->totalUpdateCount = count($this->exportData);
		$this->log($this->totalUpdateCount . " reverifications to update");

		if ($this->totalUpdateCount > 0) {
			$spreadsheetId = CoauthorSheetMaster::getSheetId();
			foreach ($this->sheetNames as $sheetName) {
				$this->log("Processing Sheet: $sheetName");
				$this->processSheet($spreadsheetId, $sheetName);
			}
		}

		$this->processRemaining();
		// not sending anything right now since this project is on hold
		// $this->sendReport();
	}

	protected function processSheet($spreadsheetId, $sheetName) {
		$updateCount = 0;
		$skipCount = 0;
		$rows = GoogleSheets::getRowsAssoc($spreadsheetId, $sheetName);
		foreach ($rows as $row => $data) {
			$rowAid = $data["ArticleID"];
			$shouldUpdateRow = true;
			if (isset($this->exportData[$rowAid]) && $rever = $this->exportData[$rowAid]) {
				$this->log("Processing Reverification:  Article ID - {$rever->getAid()}, " .
					"Reverified Date - {$rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET)}");

				$reverificationDate = $rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET);
				$spreadsheetVerifiedDate = $data["Verified Date"];

				$verifierId = (int) $data["Coauthor ID"];
				$verifierName = $data["Verifier Name"];
				if (!isset($this->verifierCount[$verifierId])) {
					$this->verifierCount[$verifierId] = 0;
					$this->verifierSkipCount[$verifierId] = 0;
				}
				if ($this->verifierCount[$verifierId] >= Self::MAX_PER_DAY) {
					$skipCount++;
					$this->verifierSkipCount[$verifierId]++;
					$this->totalSkipped++;
					$shouldUpdateRow = false; //we don't want to update the row in the db, b/c we're saving it for another day
					$this->log("-Skipping. Already processed " . $this->verifierCount[$verifierId] . " by " . $verifierName);
				} elseif (strtotime($reverificationDate) <= strtotime($spreadsheetVerifiedDate)) {
					$skipCount++;
					$this->totalSkipped++;
					$this->verifierSkipCount[$verifierId]++;
					$this->log("-Skipping.  Reverified date less than or equal to current spreadsheet date " .
						"$spreadsheetVerifiedDate.");
					$this->emailLog("Article ID: $rowAid, Reverification Date: $reverificationDate - Skipping " .
						"b/c verified date less than or equal to spreadsheet verified date ($spreadsheetVerifiedDate)");
				} else {
					$updateCount++;
					$this->totalUpdated++;
					$t = Title::newFromId($rowAid);
					if ($t && $t->exists()) {
						$this->log("-Updating spreadsheet 'Revision Link' and 'Verified Date' field");

						$newRow = $data;
						$newRevLink = Misc::getLangBaseURL('en') . $t->getLocalURL("oldid=" . $rever->getNewRevId());
						$newVerifDate = ReverificationData::formatDate(date(ReverificationData::FORMAT_DB), ReverificationData::FORMAT_SPREADSHEET);
						$newRow['Revision Link'] = $newRevLink;
						$newRow['Verified Date'] = $newVerifDate;

						if ($rever->getVerifierId() && ($rever->getVerifierId() != $verifierId) ) {
							$this->log("-Updating spreadsheet 'Coauthor ID' field. Replacing " .
							"{$data["Coauthor ID"]} with {$rever->getVerifierId()}");
							$newRow['Verifier Name'] = $rever->getVerifierName();
						}

						$range = "{$sheetName}!A{$row}:J";
						$rows = [ array_values($newRow) ];
						GoogleSheets::updateRows($spreadsheetId, $range, $rows);

						$this->verifierCount[$verifierId]++;
					} else {
						$this->log("-Skipping. Title doesn't exist for Article ID $rowAid");
						$this->emailLog("Article ID: $rowAid, Reverification Date: $reverificationDate - Title " .
							"doesn't exist for article id");
					}
				}

				if ($shouldUpdateRow) {
					$rever->setScriptExportTimestamp(wfTimestampNow());
					ReverificationDB::getInstance()->update($rever);
				}

				// Clear out the export data once it's been updated
				unset($this->exportData[$rowAid]);
			}
		}
		$this->log($updateCount . " reverifications updated");
		$this->log($skipCount . " reverifications skipped");
	}

	/**
	 * Check for orphaned export items.  Add them as errors to be reported
	 */
	protected function processRemaining() {
		foreach ($this->exportData as $aid => $rever) {
			$this->log("Reverification article id not found in spreadsheet so not updated:" . $aid);
			$this->emailLog("Reverification article id not found in spreadsheet so not updated:" . $aid);
			$rever->setScriptExportTimestamp(wfTimestampNow());
			ReverificationDB::getInstance()->update($rever);
		}
	}

	protected function sendReport() {
		$startTime = $this->startTime;
		$endTime = wfTimestampNow();
		$duration = gmdate("H:i:s", strtotime($endTime) - strtotime($startTime));
		$reportBody = "Total processed reverifications: {$this->totalUpdateCount}\n\n" .
			"Total updated: {$this->totalUpdated}\n\n" .
			"Total skipped: {$this->totalSkipped}\n\n\n\n";

		if ($this->totalUpdated > 0) {
			foreach ($this->verifierCount as $verifier => $count) {
				$reportBody .= "Total updated by {$verifier}: {$count}\n\n";
				if (isset($this->verifierSkipCount[$verifier]) && $this->verifierSkipCount[$verifier] > 0) {
					$reportBody .= "Total skipped by {$verifier}: {$count}\n\n";
				}
			}
		}

		if (!empty($this->emailLog)) {
			$reportBody .= "Notices:\n\n" . implode("\n\n", $this->emailLog) . "\n\n";
		}

		$reportBody .=	"Script Start: {$this->convertoLocalTime($startTime)}\n\n" .
			"Script End: {$this->convertoLocalTime($endTime)}\n\n" .
			"Duration: $duration";

		UserMailer::send(
			new MailAddress('jordan@wikihow.com, elizabeth@wikihow.com, connor@wikihow.com, bebeth@wikihow.com'),
			new MailAddress('ops@wikihow.com'),
			"Reverifications: Master Expert Verified Update Report - " . $this->convertoLocalTime(wfTimestampNow()),
			$reportBody
		);
	}

	protected function convertoLocalTime(String $date) {
		$dateTime = new DateTime ($date);
		$dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
		return $dateTime->format('Y-m-d H:i:s');

	}

	protected function emailLog($str) {
		$this->emailLog []= $str;
	}

	protected function log($str) {
		echo $str . "\n";
	}

	/**
	 * Get the reverifications that need to be updated in the gsheet
	 *
	 * @return ReverificationData[]
	 */
	protected function getExportData() {
		$db = ReverificationDB::getInstance();
		$exportData = $db->getScriptExport();
		foreach ($exportData as $datum) {
			$this->exportData[$datum->getAid()] = $datum;
		}

		return $this->exportData;
	}

}
