<?php
/**
 * @package     Redcore
 * @subpackage  Database
 *
 * @copyright   Copyright (C) 2012 - 2013 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_REDCORE') or die;

/**
 * MySQLi database driver
 *
 * @package     Redcore
 * @subpackage  Database
 * @since       1.0
 */
class RDatabaseMysqli extends JDatabaseMySQLi
{
	/**
	 * We can choose not to translate query with this variable
	 *
	 * @var  boolean
	 */
	public $translate = false;

	/**
	 * Constructor.
	 *
	 * @param   array  $options  List of options used to configure the connection
	 *
	 * @since   12.1
	 */
	public function __construct($options)
	{
		// Finalize initialisation.
		parent::__construct($options);
	}

	/**
	 * This function replaces a string identifier <var>$prefix</var> with the string held is the
	 * <var>tablePrefix</var> class variable.
	 *
	 * @param   string  $sql     The SQL statement to prepare.
	 * @param   string  $prefix  The common table prefix.
	 *
	 * @return  string  The processed SQL statement.
	 *
	 * @since   11.1
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		// Basic check for translations
		if ($this->translate)
		{
			if ($parsedSql = RDatabaseSqlparserSqltranslation::buildTranslationQuery($sql, $prefix))
			{
				return parent::replacePrefix($parsedSql, $prefix);
			}
		}

		return parent::replacePrefix($sql, $prefix);
	}
}
