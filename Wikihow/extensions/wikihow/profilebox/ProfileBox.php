<?php

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ProfileBox',
	'author' => 'Vu (wikiHow)',
	'description' => 'Magic word used in profile to display user data and stats',
);

$wgSpecialPages['ProfileBox'] = 'ProfileBox';
$wgAutoloadClasses['ProfileBox'] = __DIR__ . '/ProfileBox.body.php';
$wgAutoloadClasses['ProfileStats'] = __DIR__ . '/ProfileBox.body.php';
$wgExtensionMessagesFiles['ProfileBox'] = __DIR__ . "/ProfileBox.i18n.php";

$wgHooks['LocalUserCreated'][] = 'ProfileBox::onInitProfileBox';

$wgResourceModules['ext.wikihow.profile_box_styles'] = [
	'styles' => [ 'profilebox.css' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/profilebox',
	'position' => 'top',
	'targets' => ['desktop', 'mobile']
];

$wgResourceModules['ext.wikihow.profile_box'] = [
	'scripts' => [ 'profilebox.js' ],
	'messages' => [
		'profilebox_remove_confirm',
		'pb-viewmore',
		'pb-viewless'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/profilebox',
	'position' => 'bottom',
	'targets' => [ 'desktop', 'mobile'],
	'dependencies' => [
		'ext.wikihow.common_top'
	]
];

/*
CREATE TABLE `profilebox` (
  `pb_user` int(8) unsigned NOT NULL DEFAULT '0',
  `pb_started` int(11) unsigned NOT NULL DEFAULT '0',
  `pb_edits` int(11) unsigned NOT NULL DEFAULT '0',
  `pb_patrolled` int(11) unsigned NOT NULL DEFAULT '0',
  `pb_viewership` int(10) unsigned NOT NULL DEFAULT '0',
  `pb_lastUpdated` varchar(14) DEFAULT NULL,
  `pb_thumbs_given` int(11) NOT NULL DEFAULT '0',
  `pb_thumbs_received` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pb_user`)
);
*/
