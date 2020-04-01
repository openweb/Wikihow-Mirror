<?php

namespace TechArticle;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

use EditPage;
use MailAddress;
use OutputPage;
use RequestContext;
use User;
use UserMailer;
use WikiPage;

/**
 * Add the Tech Widget to the article edit page
 */
class TechArticleWidgetHooks {

	/**
	 * (EditPage::showEditForm:fields) Used by SpecialPagesHooks::onShowEditFormFields()
	 */
	public static function addWidgetToEditForm(EditPage &$form, OutputPage &$out) {
		$req = RequestContext::getMain()->getRequest();
		$user = RequestContext::getMain()->getUser();
		$page = $form->getArticle()->getPage();
		$action = $req->getVal('action');

		if (TechArticleWidgetModel::isWidgetVisible($req, $user, $out)) {
			$out->addModules('ext.wikihow.TechArticle.widget');

			list($products, $platforms) = TechArticleWidgetModel::getWidgetData($page->getId());
			$vars = [
				'page_id' => $page->getId(),
				'products' => $products,
				'platforms' => $platforms,

				'msg_heading' => wfMessage('taw_heading')->text(),
				'msg_product_title' => wfMessage('taw_product_title')->text(),
				'msg_platform_title' => wfMessage('taw_platform_title')->text(),
				'msg_dropdown_select_one' => wfMessage('taw_dropdown_select_one')->text(),
				'msg_dropdown_platform' => wfMessage('taw_dropdown_platform')->text(),
				'msg_dropdown_tested' => wfMessage('taw_dropdown_tested')->text(),
			];

			$engine = new Mustache_Engine([
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/resources' )
			]);
			$form->editFormTextAfterContent .= $engine->render('tech_article_widget.mustache', $vars);
		}
	}

	/**
	 * (PageContentSaveComplete) After the save article request has been processed.
	 */
	public static function onPageContentSaveComplete(WikiPage $page, User $user, $content,
			$summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId) {

		$req = RequestContext::getMain()->getRequest();
		if ($req->getText('taw_action') != 'update') {
			return;
		}

		list($productId, $platforms, $error) = TechArticleWidgetModel::sanitizeWidgetData(
			$req->getInt('taw_product', 0),
			$req->getIntArray('taw_platform', []),
			$req->getIntArray('taw_tested', []),
			$user
		);

		if ($error) {
			self::reportErrorByEmail($error);
		} else {
			TechArticle::newFromValues($page->getId(), $page->getLatest(),
				$user->getId(), $productId, $platforms)->save();
		}

		return true;
	}

	private static function reportErrorByEmail(string $error) {
		$to = new MailAddress('alberto@wikihow.com');
		$from = new MailAddress('alerts@wikihow.com');
		$subject = "Tech Article Widget error";

		$footer = 'Generated by ' . __FILE__ . ' on ' . gethostname() . "\n";
		$msg = "Error message: '$error'\n$footer";

		UserMailer::send($to, $from, $subject, $msg);
	}

}
