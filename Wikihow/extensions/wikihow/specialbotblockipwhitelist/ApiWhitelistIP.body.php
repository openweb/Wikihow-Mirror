<?php

class ApiWhitelistIP extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = '';

		// Check auth using secret parameter/ key
		if ( $params['ipwl_key_param'] == WH_BOTBLOCK_WHITELIST_KEY ) {
			$allIPs = BotBlockIPWhitelist::getAllWhitelistedIPs();
			$result->setIndexedTagName( $allIPs, 'ipwl_ip_addr' );
			$result->addValue( null, $module, $allIPs );
		}

		return true;
	}

	public function getAllowedParams() {
		return array(
			'ipwl_key_param' => array(
				ApiBase::PARAM_TYPE => 'string',
			)
		);
	}

	public function getParamDescription() {
		return array(
		);
	}

	public function getDescription() {
		return 'An API extension to get all whitelisted IP addresses';
	}

	public function getPossibleErrors() {
		return parent::getPossibleErrors();
	}

	public function getExamples() {
		return array(
			'api.php?action=allwhitelistip'
		);
	}

	public function getHelpUrls() {
		return '';
	}

	public function getVersion() {
		return '0.0.1';
	}

}

