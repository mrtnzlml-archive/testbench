<?php

namespace Tests\Issues;

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
		if ($shouldFail) {
			Assert::exception(function () use ($post, $shouldFail) {
				$this->checkForm('Presenter:default', 'form1', $post, $shouldFail ? FALSE : '/x/y');
			}, 'Tester\AssertException', "field 'test' returned this error(s):\n  - This field is required.");
			$errors = $this->getPresenter()->getComponent('form1')->getErrors();
			Assert::same(['This field is required.'], $errors);
		} else {
			$this->checkForm('Presenter:default', 'form1', $post, $shouldFail ? FALSE : '/x/y');
			$errors = $this->getPresenter()->getComponent('form1')->getErrors();
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
