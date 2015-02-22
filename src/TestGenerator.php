<?php

require __DIR__ . '/../../../autoload.php'; //FIXME: not good

//TODO: upozornění na přemazání již napsaných testů
//TODO: lepší generování podle situace
//TODO: 2 parametry: source a destination

$configurator = new Nette\Configurator;
$configurator->setTempDirectory(__DIR__ . '/../../../../temp'); //FIXME: not good
$loader = $configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../../../../app/')//FIXME: not good
	->register();

foreach ($loader->getIndexedClasses() as $class => $filename) {
	$refl = Nette\Reflection\ClassType::from($class);
	if ($refl->isInstantiable() && $refl->implementsInterface('Nette\Application\IPresenter')) {
		$class = new Nette\PhpGenerator\ClassType($refl->getShortName() . 'Test');
		$class->addDocument('@testCase')->setExtends('Tester\TestCase');
		$class->addProperty('tester')->setVisibility('private');
		$presName = preg_replace('/Presenter$/i', '', $refl->getShortName());
		$class->addMethod('__construct')
			->setBody('$this->tester = new PresenterTester($container, \'' . $presName . '\');')
			->addParameter('container')
			->setTypeHint('Nette\DI\Container');
		/** @var ReflectionMethod $method */
		foreach ($refl->getMethods() as $method) {
			if (preg_match('/^render([a-z]+)/i', $method->getName(), $matches)) {
				$class->addMethod('test' . ucfirst($method->getName()))
					->setBody('$this->tester->testAction(\'' . strtolower($matches[1]) . '\');');
			}
			if (preg_match('/^createComponent([a-z]+)/i', $method->getName(), $matches)) {
				$class->addMethod('testComponent' . ucfirst($matches[1]))
					->setBody('$this->tester->testForm(\'' . strtolower($matches[1]) . '\', array('
						. "\n\t//TODO: input => value\n"
						. '));');
			}
		}
		$output = "<?php\n\nnamespace Test;\n\nuse Nette;\nuse Tester;\n\n";
		$output .= '$container = require __DIR__ . \'/../bootstrap.php\';' . "\n\n";
		$output .= (string)$class;
		$output .= "\n" . '$test = new ' . $refl->getShortName() . 'Test($container);';
		$output .= "\n\n" . '$test->run();' . "\n";
		if ($refl->inNamespace()) {
			$dir = preg_replace('/\\\/', '', $refl->getNamespaceName());
			@mkdir(__DIR__ . '/../../../../tests/' . $dir);
			file_put_contents(__DIR__ . '/../../../../tests/' . $dir . DIRECTORY_SEPARATOR . $refl->getShortName() . 'Test.phpt', (string)$output);
		} else {
			//FIXME: not good: paths
			@mkdir('presenters');
			file_put_contents(__DIR__ . '/../../../../tests/' . $dir . 'presenters' . DIRECTORY_SEPARATOR . $refl->getShortName() . 'Test.phpt', (string)$output);
		}
	}
}

die("OK");
