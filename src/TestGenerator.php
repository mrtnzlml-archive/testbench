<?php

require __DIR__ . '/../vendor/autoload.php';

//TODO: upozornění na přemazání již napsaných testů
//TODO: lepší generování podle situace
//TODO: 2 parametry: source a destination

$configurator = new Nette\Configurator;
$configurator->setTempDirectory(__DIR__ . '/../temp');
$loader = $configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../tests/')
	->register();

foreach ($loader->getIndexedClasses() as $class => $filename) {
	$refl = Nette\Reflection\ClassType::from($class);
	if ($refl->isInstantiable() && $refl->implementsInterface('Nette\Application\IPresenter')) {
		$class = new Nette\PhpGenerator\ClassType($refl->getShortName() . 'Test');
		$class->addDocument('@testCase')->setExtends('Tester\TestCase');
		$class->addProperty('tester')->setVisibility('private');
		$class->addMethod('__construct')
			->setBody('$this->tester = new PresenterTester($container, \'' . $refl->getShortName() . '\');')
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
		//TODO: require container
		$output .= (string)$class;
		if ($refl->inNamespace()) {
			@mkdir($refl->getNamespaceName());
			file_put_contents($refl->getNamespaceName() . DIRECTORY_SEPARATOR . $refl->getShortName() . 'Test.phpt', (string)$output);
		} else {
			@mkdir('presenters');
			file_put_contents('presenters' . DIRECTORY_SEPARATOR . $refl->getShortName() . 'Test.phpt', (string)$output);
		}
	}
}
