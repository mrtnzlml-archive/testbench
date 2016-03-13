<?php

function __testbench_getService($class)
{
	$container = \Testbench\ContainerFactory::create(FALSE);
	return $container->getByType($class);
}
