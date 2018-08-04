<?php declare(strict_types = 1);

namespace Testbench\Providers;

use Nette\DI\Container;

/**
 * This interface is not stable yet. In fact it's really bad design and it needs refactor (stay tuned).
 */
interface IDatabaseProvider
{

	/**
	 * Perform complete database setup (should drop and create database, import sqls, run migrations).
	 * Register shutdown function only if it's not persistent setup.
	 */
	function __testbench_database_setup($connection, Container $container, $persistent = false): void;

	/**
	 * Drop database.
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_drop($connection, Container $container): void;

	/**
	 * Create new database.
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_create($connection, Container $container): void;

	/**
	 * Connect to the database.
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_connect($connection, Container $container, $databaseName = null): void;

	/**
	 * Change database as quickly as possible (USE in MySQL, connect in PostgreSQL).
	 * This function uses internal '__testbench_databaseName'. Needs refactor!
	 */
	function __testbench_database_change($connection, Container $container): void;

}
