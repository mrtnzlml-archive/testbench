<?php

namespace Testbench\Providers;

interface IDatabaseProvider
{

	function __testbench_database_setup($connection, \Nette\DI\Container $container);

	function __testbench_database_drop($connection, \Nette\DI\Container $container);

	function __testbench_database_create($connection, \Nette\DI\Container $container);

	function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = NULL);

}
