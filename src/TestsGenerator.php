<?php

namespace Testbench;

use Nette\PhpGenerator;

require dirname(__DIR__) . '/tests/bootstrap.php';

class TestsGenerator
{

	private $renderMethods = [];

	private $handleMethods = [];

	private $componentMethods = [];

	public function __construct($outputFolder = __DIR__ . '/../scaffold')
	{
		$container = \Testbench\ContainerFactory::create(FALSE);
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$presenters = $container->findByType('Nette\Application\UI\Presenter');

		foreach ($presenters as $presenter) {
			/** @var \Nette\Application\UI\Presenter $service */
			$service = $container->getService($presenter);
			if ($service instanceof \Testbench\PresenterMock) {
				continue;
			}

			$rc = new \ReflectionClass($service);
			$renderPrefix = $service->formatRenderMethod('');

			$methods = $rc->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
			foreach ($methods as $method) {
				$methodName = $method->getName();
				if (preg_match("~^{$renderPrefix}[a-z0-9]+~i", $methodName)) {
					try {
						$service->$methodName();
						$this->renderMethods[$methodName] = 'action'; //FIXME: RSS, Sitemap!
					} catch (\Nette\Application\BadRequestException $exc) {
						$this->renderMethods[$methodName] = ['404', $exc];
					} catch (\Nette\Application\AbortException $exc) {
						$this->renderMethods[$methodName] = 'redirect';
					} catch (\Exception $exc) {
						$this->renderMethods[$methodName] = ['exception', $exc];
					}
				}
				if (preg_match("~^handle[a-z0-9]+~i", $methodName)) {
					$this->handleMethods[] = $methodName;
				}
				if (preg_match("~^createComponent[a-z0-9]+~i", $methodName)) {
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
			$testClass->addDocument('@testCase');

			foreach ($this->renderMethods as $testMethod => $testMethodType) {
				$generatedMethod = $testClass->addMethod('test' . ucfirst($testMethod));
				$action = $presenterFactory->unformatPresenterClass($rc->getName()) . ':';
				$action .= lcfirst(preg_replace('~^render([a-z]+)~i', '$1', $testMethod));
				if (is_array($testMethodType)) {
					/** @var \Exception $exception */
					$exception = $testMethodType[1];
					$testMethodType = $testMethodType[0]; //FIXME: fuj, hnus
				}
				switch ($testMethodType) {
					case 'action':
						$generatedMethod->addBody('$this->checkAction(?);', [$action]);
						break;
					case '404':
						$generatedMethod->addBody(
							"Assert::exception(function () {\n"
							. "\t\$this->checkAction(?);\n"
							. "}, 'Nette\\Application\\BadRequestException');\n"
							. "Assert::same(?, \$this->getReturnCode());", [$action, $exception->getCode()]
						);
						break;
					case 'redirect':
						$generatedMethod->addBody('$this->checkRedirect(?, \'/\'); //FIXME: redirect path', [$action]);
						break;
					case 'exception':
						$generatedMethod->addBody(
							"Assert::exception(function () {\n"
							. "\t\$this->checkAction(?);\n"
							. '}, ?);', [$action, get_class($exception)]
						);
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
					if ($control->getName() === '_token_') {
						continue;
					}
					$controls .= "\t'" . $control->getName() . "' => '### VALUE ###', //FIXME\n";
				}
				$testClass->addMethod('test' . ucfirst($testMethod))->addBody(
					"\$this->checkForm(?, ?, [\n" .
					$controls .
					"], '/'); //FIXME: redirect path", [$destination . ':', $action, '']);
			}

			$generatedTest = "<?php\n\nnamespace Tests;\n\nuse Tester\\Assert;\n\n";
			$generatedTest .= "require __DIR__ . '/bootstrap.php';\n\n"; //FIXME: vyřešit úrovně zanoření
			$generatedTest .= $testClass;
			$generatedTest .= "\n(new $testClassName)->run();\n";

			$testFileName = preg_replace('~\\\~', DIRECTORY_SEPARATOR, get_class($service));
			\Nette\Utils\FileSystem::write($outputFolder . '/' . $testFileName . '.phpt', $generatedTest);
			\Nette\Utils\FileSystem::createDir($outputFolder . '/_temp');
			\Nette\Utils\FileSystem::write($outputFolder . '/bootstrap.php', <<<'BOOTSTRAP'
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

Testbench\Bootstrap::setup(__DIR__ . '/_temp', function (Nette\Configurator $configurator) {
	$configurator->addParameters([
		//'appDir' => __DIR__ . '/../src',
		//'testsDir' => __DIR__,
	]);

	$configurator->addConfig(__DIR__ . '/tests.neon');
});

BOOTSTRAP
			);
			\Nette\Utils\FileSystem::write($outputFolder . '/tests.neon', <<<'NEON'
# WARNING: it is CRITICAL that this file & directory are NOT accessible directly via a web browser!
# http://nette.org/security-warning


routing:
	routes:
		'/x/y[[[/<presenter>]/<action>][/<id>]]': 'Presenter:default'

NEON
			);
		}
	}

}

(new TestsGenerator);
