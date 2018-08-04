<?php declare(strict_types = 1);

namespace Testbench;

use Nette\ComponentModel\IComponent;
use Nette\InvalidArgumentException;
use Tester\Assert;

trait TComponent
{

	private $__testbench_presenterMock;

	protected function attachToPresenter(IComponent $component, $name = null): void
	{
		if ($name === null) {
			if (!$name = $component->getName()) {
				$name = $component->getReflection()->getShortName();
				if (preg_match('~class@anonymous.*~', $name)) {
					$name = md5($name);
				}
			}
		}
		if (!$this->__testbench_presenterMock) {
			$container = ContainerFactory::create(false);
			$this->__testbench_presenterMock = $container->getByType('Testbench\Mocks\PresenterMock');
			$container->callInjects($this->__testbench_presenterMock);
		}
		$this->__testbench_presenterMock->onStartup[] = function (Mocks\PresenterMock $presenter) use ($component, $name): void {
			try {
				$presenter->removeComponent($component);
			} catch (InvalidArgumentException $exc) {
			}
			$presenter->addComponent($component, $name);
		};
		$this->__testbench_presenterMock->run(new Mocks\ApplicationRequestMock());
	}

	protected function checkRenderOutput(IComponent $control, $expected, array $renderParameters = []): void
	{
		if (!$control->getParent()) {
			$this->attachToPresenter($control);
		}
		ob_start();
		$control->render(...$renderParameters);
		if (is_file($expected)) {
			Assert::matchFile($expected, ob_get_clean());
		} else {
			Assert::match($expected, ob_get_clean());
		}
	}

}
