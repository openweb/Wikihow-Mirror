<?php

class ExpertAdviceSection {

	private static function expertHtml( OutputPage $out ): String {
		$title = $out->getTitle();

		if ($title) {
			$vdata = VerifyData::getByPageId( $title->getArticleId() );

			if (!isset($vdata[0])) return '';

			$vdata = $vdata[0];

			$loader = new Mustache_Loader_CascadingLoader( [
				new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
			] );
			$m = new Mustache_Engine(['loader' => $loader]);

			$vars = [
				'name' => $vdata->name,
				'label' => $vdata->blurb,
				'name_url' => ArticleReviewers::getLinkToCoauthor($vdata)
			];

			$html = $m->render('expert_advice_expert.mustache', $vars);
		}

		return $html;
	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin $skin ) {
		$title = $out->getTitle();
		if ($title && $title->inNamespace(NS_MAIN)) {
			$out->addModuleStyles('ext.wikihow.expertadvicesection.styles');
		}
	}

	//this uses the phpQuery object
	//can be Expert Advice (expertadvice) or Expert Q&A (expertqampa)
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		$section_name = pq('#expertadvice')->length() ? 'expertadvice' : '';
		if ($section_name == '' && pq('#expertqampa')->length()) $section_name = 'expertqampa';
		if ($section_name == '') return;

		$expertHtml = self::expertHtml( $out );
		pq('#'.$section_name)->append($expertHtml);
		pq('#'.$section_name)->addClass('expert_advice_section');

		//put above the Q&A section
		if (pq('.qa.section')->length()) pq('.qa.section')->before(pq('.'.$section_name));

		// $section_text = wfMessage($section_name)->text();
		$section_text = pq('.'.$section_name)->find('h2 span.mw-headline')->text();
		pq(".expertadvice.section")->find('h2')->prepend("<div class='altblock'></div>");

		//add it to desktop TOC
		WikihowToc::setExpertAdvice($section_name, $section_text);
	}
}