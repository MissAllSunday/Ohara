<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Db
{
	protected $_app;

	public function __construct($app)
	{
		$this->_app = $app;
	}

	/**
	 * Updates data in x table. Its highly integrated with {@link $_dbSchema} meaning this can't be used to update external tables, use {@link updateExt()} for that.
	 * Uses {@link $_dbSchema}
	 * @access public
	 * @param array $ids An array of ids to check againts, can also use an int.
	 * @param array $params An array of params (ohrly???) $column => $value pair.
	 * @param array $info the name of the table to update and the column. array(0 => 'table', 1 => 'column')
	 * @return void
	 */
	public function update($ids = array(), $params = array(), $info = array())
	{
		global $smcFunc;

		// Basic checks.
		if (!$this->_app->_dbSchema || (empty($params) || !is_array($params)) || empty($ids))
			return false;

		// Work with arrays.
		$ids = (array) $ids;

		// $info must contain a table name and a column name. $info[0] table name, $info[1] column name.
		if (empty($info) || empty($info[0]) || empty($info[1]))
			return false;

		// What table should be updated?  defaults to $this->_app->_dbSchema[0] if no table was specified.
		$table = isset($this->_app->_dbSchema[$info[0]]) ? $this->_app->_dbSchema[$info[0]] : (isset($this->_app->_dbSchema[0]) ? $this->_app->_dbSchema[0] : $info[0]);

		// At this point there should be something, perhaps its a vanilla table or a typo or a non-existant table. If theres something, use it.
		if (empty($table))
			return false;

		// Create a nice formatted string.
		$string = '';

		foreach ($params as $column => $newValue)
			$string .= $column .' = '. $newValue . ($newValue != end($params) ? ', ' : '');

		return $smcFunc['db_query']('', '
			UPDATE {db_prefix}' . ($table) . '
			SET '. ($string) .'
			WHERE '. ($column) .' IN ({array_int:ids})',
			array(
				'ids' => $ids,
			)
		);
	}
}
