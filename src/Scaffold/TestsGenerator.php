<?php

namespace Testbench\Scaffold;

use Nette\PhpGenerator;

class TestsGenerator
{

	private $renderMethods = [];

	private $handleMethods = [];

	private $componentMethods = [];

	public function generateTests($outputFolder)
	{
		$container = \Testbench\ContainerFactory::create(FALSE);
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$presenters = $container->findByType('Nette\Application\UI\Presenter');

		foreach ($presenters as $presenter) {
			$this->renderMethods = $this->handleMethods = $this->componentMethods = [];

			/** @var \Nette\Application\UI\Presenter $service */
			$service = $container->getService($presenter);
			if ($service instanceof \Testbench\Mocks\PresenterMock) {
				continue;
			}
			if ($service instanceof \KdybyModule\CliPresenter) { //Oh, Kdyby! :-(
				continue;
			}

			$rc = new \ReflectionClass($service);
			$renderPrefix = $service->formatActionMethod('') . '|' . $service->formatRenderMethod('');

			$methods = $rc->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
			foreach ($methods as $method) {
				$methodName = $method->getName();
				if (preg_match("~^({$renderPrefix})[a-z0-9]+~i", $methodName)) {
					try {
						$optionalArgs = $this->tryCall($service, $methodName, $service->getParameters(), TRUE);
						if (preg_match('~.*rss.*~i', $methodName)) {
							$this->renderMethods[$methodName] = 'rss';
						} elseif (preg_match('~.*sitemap.*~i', $methodName)) {
							$this->renderMethods[$methodName] = 'sitemap';
						} else {
							$requiredArgs = $this->tryCall($service, $methodName, $service->getParameters(), FALSE);
							$this->renderMethods[$methodName] = ['action', [$optionalArgs, $requiredArgs]];
						}
					} catch (\Nette\Application\AbortException $exc) {
						$this->renderMethods[$methodName] = ['action', $this->getResponse($service)];
					} catch (\Exception $exc) {
						$this->renderMethods[$methodName] = ['exception', $exc];
					}
				}
				if (preg_match('~^handle[a-z0-9]+~i', $methodName)) {
					if ($methodName === 'handleInvalidLink') { //internal method
						continue;
					}
					$this->handleMethods[] = $methodName;
				}
				if (preg_match('~^createComponent[a-z0-9]+~i', $methodName)) {
					$method->setAccessible(TRUE);
					$form = $method->invoke($service);
					if ($form instanceof \Nette\Application\UI\Form) {
						$this->componentMethods[$methodName] = $form;
					}
				}
			}

			$testClassName = $rc->getShortName() . 'Test';
			$testClass = new PhpGenerator\ClassType($testClassName);
			$testClass->setExtends('\Tester\TestCase');
			$testClass->addTrait('\Testbench\TPresenter');
			$testClass->addComment('@testCase');

			foreach ($this->renderMethods as $testMethod => $testMethodType) {
				$generatedMethod = $testClass->addMethod('test' . ucfirst($testMethod));
				$destination = $presenterFactory->unformatPresenterClass($rc->getName()) . ':';
				$destination .= lcfirst(preg_replace('~^(action|render)([a-z]+)~i', '$2', $testMethod));
				$extra = NULL;
				if (is_array($testMethodType)) {
					/** @var \Exception|\Nette\Application\IResponse $extra */
					$extra = $testMethodType[1];
					$testMethodType = $testMethodType[0]; //FIXME: fuj, hnus
				}
				switch ($testMethodType) {
					case 'rss':
						$generatedMethod->addBody('$this->checkRss(?);', [$destination]);
						break;
					case 'sitemap':
						$generatedMethod->addBody('$this->checkSitemap(?);', [$destination]);
						break;
					case 'action':
						if ($extra instanceof \Nette\Application\Responses\RedirectResponse) {
							$url = new \Nette\Http\Url($extra->getUrl());
							$generatedMethod->addBody('$this->checkRedirect(?, ?);', [$destination, $url->getPath()]);
						} elseif ($extra instanceof \Nette\Application\Responses\JsonResponse) {
							$generatedMethod->addBody('$this->checkJson(?);', [$destination]);
						} else {
							if ($extra[0]) {
								$generatedMethod->addBody('//FIXME: parameters may not be correct');
								$generatedMethod->addBody("\$this->checkAction(?, ?);\n", [$destination, $extra[0]]);
								$generatedMethod->addBody('$this->checkAction(?, ?);', [$destination, $extra[1]]);
							} else {
								$generatedMethod->addBody('$this->checkAction(?);', [$destination]);
							}
						}
						break;
					case 'exception':
						$this->generateExceptionBody($generatedMethod, $destination, $extra);
						break;
				}
			}

			foreach ($this->handleMethods as $testMethod) {
				$destination = $presenterFactory->unformatPresenterClass($rc->getName());
				$action = lcfirst(preg_replace('~^handle([a-z]+)~i', '$1', $testMethod));
				$testClass->addMethod('test' . ucfirst($testMethod))
					->addBody('$this->checkSignal(?, ?);', [$destination . ':', $action]);
			}

			foreach ($this->componentMethods as $testMethod => $form) {
				$destination = $presenterFactory->unformatPresenterClass($rc->getName());
				$action = lcfirst(preg_replace('~^createComponent([a-z]+)~i', '$1', $testMethod));
				$controls = '';
				/** @var \Nette\Application\UI\Form $form */
				foreach ($form->getControls() as $control) {
					if ($control->getName() === '_token_' || $control instanceof \Nette\Forms\Controls\SubmitButton) {
						continue;
					}
					$value = "'###', //FIXME: replace with value";
					if ($control instanceof \Nette\Forms\Controls\Checkbox) {
						$value = 'FALSE,';
					}
					$controls .= "\t'" . $control->getName() . "' => $value\n";
				}
				try {
					$form->onSuccess($form, $form->getValues());
					$testClass->addMethod('test' . ucfirst($testMethod))->addBody(
						"\$this->checkForm(?, ?, [\n" . $controls . '], ?);',
						[$destination . ':', $action, FALSE]
					);
				} catch (\Nette\Application\AbortException $exc) {
					$extra = $this->getResponse($service);
					$path = $extra ? (new \Nette\Http\Url($extra->getUrl()))->getPath() : '/';
					$testClass->addMethod('test' . ucfirst($testMethod))->addBody(
						"\$this->checkForm(?, ?, [\n" . $controls . '], ?);',
						[$destination . ':', $action, $path]
					);
				} catch (\Exception $exc) {
					//This sucks but we have to move on - failure is not an option
				}
			}

			$namespace = $rc->getNamespaceName();
			$namespace = $namespace ? '\\' . $namespace : '';
			$generatedTest = "<?php\n\nnamespace Tests$namespace;\n\nuse Tester\\Assert;\n\n";
			$depth = substr_count($namespace, '\\');
			$levelsUp = str_repeat('../', $depth);
			$generatedTest .= "require __DIR__ . '/{$levelsUp}bootstrap.php';\n\n";
			$generatedTest .= $testClass;
			$generatedTest .= "\n(new $testClassName)->run();\n";

			$testFileName = preg_replace('~\\\~', DIRECTORY_SEPARATOR, get_class($service));
			\Nette\Utils\FileSystem::write($outputFolder . '/' . $testFileName . '.phpt', $generatedTest);
			\Nette\Utils\FileSystem::createDir($outputFolder . '/_temp');
			\Nette\Utils\FileSystem::write($outputFolder . '/tests.neon', <<<'NEON'
# WARNING: it is CRITICAL that this file & directory are NOT accessible directly via a web browser!
# http://nette.org/security-warning

# (this is just an example)

routing:
	routes:
		'/x/y[[[/<presenter>]/<action>][/<id>]]': 'Presenter:default'

NEON
			);
		}
	}

	private function generateExceptionBody(PhpGenerator\Method $method, $destination, \Exception $exception)
	{
		$method->addBody(
			"Assert::exception(function () {\n"
			. "\t\$this->checkAction(?);\n"
			. '}, ?);', [$destination, get_class($exception)]
		);
		if ($exception instanceof \Nette\Application\BadRequestException) {
			$method->addBody("Assert::same(?, \$this->getReturnCode());", [$exception->getCode()]);
		}
		return $method;
	}

	private function getResponse($service)
	{
		$property = new \ReflectionProperty(get_parent_class($service), 'response');
		$property->setAccessible(TRUE);
		return $property->getValue($service);
	}

	/**
	 * @return bool FALSE if method doesn't exist or array of args
	 * @throws \Nette\Application\BadRequestException
	 */
	private function tryCall($class, $method, array $params, $includeOptionals = TRUE)
	{
		$rc = new \Nette\Application\UI\ComponentReflection($class);
		if ($rc->hasMethod($method)) {
			$rm = $rc->getMethod($method);
			if ($rm->isPublic() && !$rm->isAbstract() && !$rm->isStatic()) {
				$args = [];
				foreach ($rm->getParameters() as $parameter) {
					if ($parameter->isOptional()) {
						if ($includeOptionals) {
							$args[$parameter->getName()] = $parameter->getDefaultValue();
						}
					} else {
						$args[$parameter->getName()] = NULL;
					}
				}
				$rm->invokeArgs($class, $rc->combineArgs($rm, $params));
				return $args;
			}
		}
		return FALSE;
	}

}
