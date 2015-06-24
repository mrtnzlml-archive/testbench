<?php

class PresenterPresenter extends Nette\Application\UI\Presenter {

	public function actionJson() {
		$this->sendResponse(new \Nette\Application\Responses\JsonResponse(array('OK')));
	}

	public function renderDefault() {
		$this->template->variable = 'test';
	}

	public function renderFail() {
		$this->error(NULL, \Nette\Http\IResponse::S500_INTERNAL_SERVER_ERROR);
	}

	public function renderException() {
		throw new \Latte\CompileException;
	}

	public function renderRedirect() {
		$this->flashMessage('Because of _fid parameter to the URL...');
		$this->redirect('default');
	}

	public function renderRedirectRss() {
		$this->flashMessage('Because of _fid parameter to the URL...');
		$this->redirect('rss');
	}

	public function renderRss() {
		$this->template->posts = array(
			\Nette\Utils\ArrayHash::from(array(
				'title' => 'title 1',
				'content' => 'content 1',
			)),
			\Nette\Utils\ArrayHash::from(array(
				'title' => 'title 1',
				'content' => 'content 1',
			)),
		);
	}

	public function renderSitemap() {
		$this->template->sitemap = array(0, 1, 2); //dumb
	}

	protected function createComponentForm() {
		$form = new \Nette\Application\UI\Form();
		$form->addText('test');
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded($form, $values) {
		$this->flashMessage(json_encode($values));
		$this->redirect('this');
	}

	public function handleSignal() {
		$this->flashMessage('OK');
		$this->redirect('this');
	}

}
