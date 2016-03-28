<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/17
 */
class Issue_17 extends \Tester\TestCase
{

	use \Testbench\TPresenter;

	/**
	 * @dataProvider commentFormParameters
	 */
	public function testCommentForm($post, $shouldFail = TRUE)
	{
		$this->check('Presenter:default', [], $post);
		$errors = $this->getPresenter()->getComponent('form1')->getErrors();
		if ($shouldFail) {
			Assert::same(['This field is required.'], $errors);
		} else {
			Assert::same([], $errors);
		}
	}

	/**
	 * @dataProvider commentFormParametersBetter
	 */
	public function testCommentFormBetter($post, $shouldFail = TRUE)
	{
		$this->checkForm('Presenter:default', 'form1', $post, $shouldFail ? FALSE : '/');
		$errors = $this->getPresenter()->getComponent('form1')->getErrors();
		if ($shouldFail) {
			Assert::same(['This field is required.'], $errors);
		} else {
			Assert::same([], $errors);
		}
	}

	public function commentFormParameters()
	{
		return [
			[['test' => NULL, 'do' => 'form1-submit'], TRUE],
			[['test' => 'NOT NULL', 'do' => 'form1-submit'], FALSE],
		];
	}

	public function commentFormParametersBetter()
	{
		return [
			[['test' => NULL], TRUE],
			[['test' => 'NOT NULL'], FALSE],
		];
	}

}

(new Issue_17)->run();
