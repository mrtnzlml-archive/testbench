<?php

namespace Testbench\Providers;

/**
 * This interface is not stable yet. In fact it's really bad design and it needs refactor (stay tuned).
 */
interface IDatabaseProvider
{

	/**
	 * Perform complete database setup (should drop and create database, import sqls, run migrations).
	 * Register shutdown function only if it's not persistent setup.
	 */
	function __testbench_database_setup($connection, \Nette\DI\Container $container, $persistent = FALSE);

	/**
	 * Drop database.
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_drop($connection, \Nette\DI\Container $container);

	/**
	 * Create new database.
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_create($connection, \Nette\DI\Container $container);

	/**
	 * Connect to the database.
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = NULL);

	/**
	 * Change database as quickly as possible (USE in MySQL, connect in PostgreSQL).
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_change($connection, \Nette\DI\Container $container);

}
