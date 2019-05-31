<?php

class AdminCoauthorIntl extends UnlistedSpecialPage
{
	public function __construct() {
		parent::__construct('AdminCoauthorIntl');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( $user->isBlocked() || !in_array('staff', $user->getGroups()) ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( ! $req->wasPosted() ) {
			$out->setPageTitle('Admin Coauthor INTL');
			$out->addModules('ext.wikihow.AdminCoauthorIntl');
			$out->addHTML( $this->getHtml() );
		}
		else {
			$token = $req->getText('token');
			$action = $req->getText('action');
			if ( !$user->matchEditToken($token) ) {
				Misc::jsonResponse( 'Not authorized.', 400 );
			}
			elseif ( $action != 'import' ) {
				Misc::jsonResponse( 'Action not supported.', 400 );
			}
			else {
				ini_set('memory_limit', '1024M');
				$stats = CoauthorSheetIntl::doImport();
				Misc::jsonResponse( $this->getHtml($stats) );
			}
		}
	}

	private function getHtml($stats = []): string {
		$m = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)]);
		return $m->render('AdminCoauthorIntl.mustache', [
			'token' => $this->getUser()->getEditToken(),
			'sheetLink' => 'https://docs.google.com/spreadsheets/d/' . CoauthorSheetIntl::getSheetId(),
			'imported' => $stats['imported'] ?? [],
			'errors' => $stats['errors'] ?? [],
			'warnings' => $stats['warnings'] ?? [],
		]);
	}

}
