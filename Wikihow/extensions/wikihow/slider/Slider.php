<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['special'][] = array(
	'name' => 'Slider',
	'author' => 'Scott Cushman',
	'description' => 'The box that slides in to prompt the user for more stuff.',
);

$wgAutoloadClasses['Slider'] = __DIR__ . '/Slider.class.php';
$wgExtensionMessagesFiles['Slider'] = __DIR__ . '/Slider.i18n.php';

$wgResourceModules['ext.wikihow.slider_styles'] = [
	'styles' => [ 'slider.css' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/slider',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.slider'] = [
	'scripts' => [ 'slider.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/slider',
	'messages' => [
		'slider_cta_video',
		'slider_url_text_video',
		'slider_cta_category',
		'slider_url_text_category',
		'newsletter_url',
		'slider_cta_newsletter',
		'slider_newsletter',
		'slider_url_text_newsletter',
		"slider_cta_marriage",
		"slider_marriage",
		"slider_url_text_marriage",
		"slider_url_marriage",
		"slider_cta_dog",
		"slider_dog",
		"slider_url_text_dog",
		"slider_url_dog",
		"slider_cta_literary",
		"slider_literary",
		"slider_url_text_literary",
		"slider_url_literary_flies",
		"slider_url_literary_brave",
		"slider_url_literary_mockingbird",
		"slider_cta_baking",
		"slider_baking",
		"slider_url_text_baking",
		"slider_url_baking",
	],
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];
