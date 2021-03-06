<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Nextras\Migrations\Entities\File;


/**
 * @author Petr Procházka
 */
interface IPrinter
{

	/**
	 * Database has been wiped. Occurs only in reset mode.
	 */
	function printReset();


	/**
	 * List of migrations which should be executed has been completed.
	 * @param  File[]
	 */
	function printToExecute(array $toExecute);


	/**
	 * A migration has been successfully executed.
	 * @param  File
	 * @param  int  number of executed queries
	 */
	function printExecute(File $file, $count);


	/**
	 * All migrations have been successfully executed.
	 */
	function printDone();


	/**
	 * An error has occured during execution of a migration.
	 * @param  Exception
	 */
	function printError(Exception $e);


	/**
	 * Prints init source code.
	 * @param  string
	 */
	function printSource($code);

}
