<?php
/**
 *  Utility class of filters for Title arrays
 */

class TitleFilters {

	const EXPLICIT_AIDS = [
		176663,
		252168,
		150400,
		1053162,
		1522113,
		1413819,
		1263741,
		1940459,
		3395060,
		3479927,
		3566989,
		1863100,
		3568238,
		8917,
		705087,
		2854496,
		203548,
		1099732,
		497548,
		787303,
		1718,
		1323321,
		109585,
		29882,
		18243,
		270480,
		880471,
		1260247,
		28777,
		1715691,
		2670115,
		1540697,
		1857155,
		2688383,
		1300065,
		205114,
		398626,
		1444793,
		192336,
		920818,
		722553,
		2097951,
		2157895,
		2224776,
		717704,
		398881,
		1040461,
		1750743,
		46339,
		403409,
		14997,
		68441,
		226845,
		31814,
		1323324,
		1251061,
		360273,
		339172,
		345276,
		3017964,
		15807,
		20800,
		25470,
		26491,
		32680,
		49133,
		56811,
		24618,
		26444,
		36903,
		14997,
		64055,
		1486373,
		27318,
		47752,
		156425,
		269480,
		689533,
		726983,
		756921,
		806798,
		895523,
		1027621,
		1357318,
		1486373,
		2432238,
		2652547,
		3313618,
		1383676,
		407333,
		6422190,
		5251435,
		82813,
		698490,
		269480,
		717125,
		1138260,
		716916,
		3507446,
		1133428,
		497548,
	];

	const EXPLICIT_AIDS_ALEXA = [
		1021881,
		1027621,
		1040461,
		1053162,
		109585,
		1099732,
		1133428,
		1138260,
		1251061,
		1260247,
		1263741,
		1300065,
		1323321,
		1323324,
		1357318,
		1383676,
		1413819,
		1444793,
		1486373,
		14997,
		150400,
		1522113,
		1540697,
		156425,
		15807,
		1715691,
		1718,
		1750743,
		176663,
		18243,
		1857155,
		1863100,
		192336,
		1940459,
		203548,
		205114,
		20800,
		2097951,
		2157895,
		2224776,
		226845,
		2432238,
		24618,
		252168,
		253379,
		25470,
		26444,
		26491,
		2652547,
		2670115,
		2688383,
		269480,
		270480,
		27318,
		2854496,
		28777,
		29882,
		3017964,
		31814,
		3183249,
		32680,
		3313618,
		339172,
		3395060,
		345276,
		3479927,
		3507446,
		3566989,
		3568238,
		360273,
		3648718,
		36903,
		398626,
		398881,
		403409,
		407238,
		407333,
		430804,
		46339,
		47752,
		478009,
		481445,
		49133,
		497548,
		5251435,
		56811,
		5959045,
		59980,
		64055,
		6422190,
		68441,
		689533,
		698490,
		705087,
		716916,
		717125,
		717704,
		722553,
		726983,
		756921,
		787303,
		806798,
		815883,
		82813,
		880471,
		8917,
		895523,
		920818,
		1506880,
		4112738,
		1509350,
		301199,
		2287742,
		2669864,
		3042371,
		1511023,
		598879,
		180109,
		705480,
		690055,
		40037,
		1220680,
		976737,
		2712275,
		4859984,
		1591686,
		3093304,
		1855526,
		2322892,
		3051484,
		536121,
		179507,
		3838938,
		230602,
		434776,
		447817,
		1165322,
		40500,
		650388,
		193686,
		12166,
		1092428,
		834254,
		129781,
		206255,
		3416058,
		11754,
		10268,
		855309,
		1194676,
		11468,
		7375,
		3136265,
		774512,
		69767,
		55488,
		21895,
		1700192,
		156542,
		3616969,
		35245,
		8041,
		2697666,
		156392,
		712729,
		1853668,
		8157,
		655949,
		375362,
		140091,
		1662292,
		697625,
		545724,
		300949,
		1011491,
		286063,
		5630,
		79488,
		13397,
		2660480,
		1756524,
		49547,
		6076,
		7156,
		279994,
		9523,
		58500,
		191738,
		441169,
		37436,
		47533,
		566907,
		32543,
		1184001,
		570803,
		15934,
		382793,
		528879,
		9732,
		220347,
		2320,
		2672961,
		2097494,
		74588,
		670569,
		1883341,
		5878003,
		6261,
		7421,
		17222,
		58668,
		450488,
		581265,
		1226186,
		1274230,
		1300587,
		1320107,
		1335416,
		1358937,
		2286665,
		2681115,
		2733204,
		2780535,
		2806271,
		2947905,
		3026261,
		3093684,
		3093692,
		3093694,
		3260161,
		3267870,
		3269943,
		3370350,
		3423372,
		3447336,
		3453670,
		3563429,
		3684787,
		4258895,
		4667468,
		5497799,
		5894219,
		6476448,
		139237,
		1199894,
		1224625,
		236150,
		552229,
		570257,
		751115,
		789658,
		1469812,
		1555969,
		1563214,
		1778218,
		2568584,
		3506294,
		3833191,
		4374416,
		4375117,
		4790765,
		4982636,
		5350049,
		6693543,
		8774117,
		7786281,
		1178620,
		165565,
		1280972,
		1817758,
		4708385,
		117632,
	];

	public static function filterByNamespace($titles, $namespacesAllowed = [NS_MAIN, NS_CATEGORY]) {
		return array_filter($titles, function($t) use ($namespacesAllowed) {
			return $t->inNamespaces($namespacesAllowed);
		});
	}

	/**
	 * Filter titles by one or more top-level categories.
	 *
	 * @param Title[] $titles an array of titles to perform the filtering
	 * @param int[] $topLevelCategories an array of top level category values, as defined in $wgCategoryNames keys
	 * @return Title[] filtered array of titles with specified top-level categories removed
	 *
	 * Ex usage: TitleFilters::filterTopLevelCategories($titles, [CAT_RELATIONSHIPS]);
	 */
	public static function filterTopLevelCategories($titles, $topLevelCategories = []) {
		// No cats or titles? No filtering necessary
		if (empty($topLevelCategories) || empty($titles)) {
			return $titles;
		}

		$titlesMap = [];
		foreach ($titles as $t) {
			if ($t && $t->exists()) {
				$titlesMap[$t->getArticleId()]= $t;
			}
		}

		// Get the catinfo bitmasks for all the titles
		$dbr = wfGetDB(DB_SLAVE);
		$rows = $dbr->select(
			['page'],
			['page_id', 'page_catinfo', 'page_title'],
			['page_id' => array_keys($titlesMap)],
			__METHOD__
		);

		// Remove the title if there's a match for a category that should be filtered
		foreach ($rows as $row) {
			foreach ($topLevelCategories as $catMask) {
				if ((int)$row->page_catinfo & $catMask) {
					unset($titlesMap[$row->page_id]);
					break;
				}
			}
		}

		return array_values($titlesMap);
	}

	public static function filterByName($titles, $dbKeysToRemove = []) {
		return array_filter($titles, function($t) use ($dbKeysToRemove) {
			return !in_array($t->getDBKey(), $dbKeysToRemove);
		});
	}

	public static function filterByAid($titles, $aidsToRemove = []) {
		return array_filter($titles, function($t) use ($aidsToRemove) {
			return !in_array($t->getArticleId(), $aidsToRemove);
		});
	}

	public static function filterExplicitAids($titles) {
		return self::filterByAid($titles, self::EXPLICIT_AIDS);
	}

	public static function filterExplicitAidsForAlexa($titles) {
		return self::filterByAid($titles, self::EXPLICIT_AIDS_ALEXA);
	}

	public static function removeRedirects($titles) {
		$filtered = [];
		foreach ($titles as $t) {
			if (!$t->isRedirect()) {
				$filtered []= $t;
			}
		}

		return $filtered;
	}
}