<?php

use Nette\Application\UI;

class PresenterPresenter extends Nette\Application\UI\Presenter
{

	/** @persistent */
	public $persistentParameter;

	public function actionJson()
	{
		$this->sendResponse(new \Nette\Application\Responses\JsonResponse([
			'string' => [
				1234 => [],
			],
		]));
	}

	public function renderDefault()
	{
		$this->template->variable = 'test';
	}

	public function renderFail()
	{
		$this->error(NULL, \Nette\Http\IResponse::S500_INTERNAL_SERVER_ERROR);
	}

	public function renderException()
	{
		throw new \Latte\CompileException;
	}

	public function renderRedirect()
	{
		$this->flashMessage('Because of _fid parameter to the URL...');
		$this->redirect('default');
	}

	public function renderRedirectRss($flashMessage = TRUE)
	{
		if ($flashMessage) {
			$this->flashMessage('Because of _fid parameter to the URL...');
		}
		$this->redirect('rss');
	}

	public function renderRss()
	{
		$this->template->posts = [
			\Nette\Utils\ArrayHash::from([
				'title' => 'title 1',
				'content' => 'content 1',
			]),
			\Nette\Utils\ArrayHash::from([
				'title' => 'title 1',
				'content' => 'content 1',
			]),
		];
	}

	public function renderSitemap()
	{
		$this->template->sitemap = [0, 1, 2]; //dumb
	}

	protected function createComponentForm1()
	{
		$form = new UI\Form();
		$form->addText('test')->setRequired();
		$form->addText('error');
		$form->onSuccess[] = function (UI\Form $form, $values) {
			if (!empty($values->error) && $values->error !== '###') { //scaffold
				$form->addError('Intended error: ' . $values->error);
			}
			$this->flashMessage(json_encode($values));
			$this->redirect('this');
		};
		return $form;
	}

	protected function createComponentForm2()
	{
		$form = new UI\Form();
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			$this->redirect('json');
		};
		return $form;
	}

	protected function createComponentForm3()
	{
		$form = new \Nette\Application\UI\Form();
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
		};
		return $form;
	}

	protected function createComponentForm4()
	{
		$form = new UI\Form();
		$form->addText('test'); //should be required, but it's not
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			$this->redirect('this');
		};
		return $form;
	}

	protected function createComponentFormWithCheckbox()
	{
		$form = new \Nette\Application\UI\Form();
		$form->addCheckbox('hello');
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			$this->redirect('this');
		};
		return $form;
	}

	protected function createComponentAjaxForm()
	{
		$form = new UI\Form();
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			if ($this->isAjax()) {
				$this->redrawControl();
			} else {
				$this->redirect('json');
			}
		};
		return $form;
	}

	protected function createComponentCsrfForm()
	{
		$form = new UI\Form();
		$form->addProtection('CSRF protection applied!');
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->redirect('this');
		};
		return $form;
	}

	public function handleSignal()
	{
		$this->flashMessage('OK');
		$this->redirect('this');
	}

	public function handleAjaxSignal()
	{
		$this->flashMessage('OK');
		if ($this->isAjax()) {
			$this->sendJson(['ok']);
		} else {
			$this->redirect('this');
		}
	}

}
