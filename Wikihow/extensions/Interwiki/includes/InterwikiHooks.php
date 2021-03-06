<?php

use MediaWiki\MediaWikiServices;

class InterwikiHooks {
	public static function onExtensionFunctions() {
		global $wgInterwikiViewOnly;

		if ( !$wgInterwikiViewOnly ) {
			global $wgLogTypes;

			// Set up the new log type - interwiki actions are logged to this new log
			// TODO: Move this out of an extension function once T200385 is implemented.
			$wgLogTypes[] = 'interwiki';
		}
	}

	/**
	 * @param array &$rights
	 */
	public static function onUserGetAllRights( array &$rights ) {
		global $wgInterwikiViewOnly;
		if ( !$wgInterwikiViewOnly ) {
			// New user right, required to modify the interwiki table through Special:Interwiki
			$rights[] = 'interwiki';
		}
	}

	public static function onInterwikiLoadPrefix( $prefix, &$iwData ) {
		global $wgInterwikiCentralDB;
		// docs/hooks.txt says: Return true without providing an interwiki to continue interwiki search.
		if ( $wgInterwikiCentralDB === null || $wgInterwikiCentralDB === wfWikiID() ) {
			// No global set or this is global, nothing to add
			return true;
		}
		if ( !Language::fetchLanguageName( $prefix ) ) {
			// Check if prefix exists locally and skip
			$lookup = MediaWikiServices::getInstance()->getInterwikiLookup();
			foreach ( $lookup->getAllPrefixes( null ) as $id => $localPrefixInfo ) {
				if ( $prefix === $localPrefixInfo['iw_prefix'] ) {
					return true;
				}
			}
			$dbr = wfGetDB( DB_REPLICA, [], $wgInterwikiCentralDB );
			$res = $dbr->selectRow(
				'interwiki',
				'*',
				[ 'iw_prefix' => $prefix ],
				__METHOD__
			);
			if ( !$res ) {
				return true;
			}
			// Excplicitly make this an array since it's expected to be one
			$iwData = (array)$res;
			// At this point, we can safely return false because we know that we have something
			return false;
		}
		return true;
	}

}
