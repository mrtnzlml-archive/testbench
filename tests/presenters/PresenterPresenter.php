<?php

class PresenterPresenter extends Nette\Application\UI\Presenter {

	public function actionJson() {
		$this->sendResponse(new \Nette\Application\Responses\JsonResponse(['OK']));
	}

	public function renderDefault() {
		$this->template->variable = 'test';
	}

	public function renderFail() {
		$this->error(NULL, \Nette\Http\IResponse::S500_INTERNAL_SERVER_ERROR);
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

}
