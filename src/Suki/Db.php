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

	/**
	 * Property for specifiying your tables, columns and types:
	 * 'table1' => array(
	 *	'column1' => 'type',
	 *	'column2' => 'int',
	 *	'column3' => 'string',
	 * ),
	 * @access protected
	 * @var array
	 */
	protected $_schema;
	protected $_keys;

	public function __construct($app)
	{
		$this->_app = $app;

		// Get the schema from our config file.
		$this->_schema = $this->_app['config']->get('dbSchema');

		// Same for the keys.
		$this->_keys = $this->_app['config']->get('dbKeys');
	}

	/**
	 * Create s anew entry on x table.
	 * Uses {@link $_schema}
	 * Uses {@link $_tableKeys}
	 * @access public
	 * @param array $data An array of data to be inserted,  array('column' => 'value')
	 * @param string $table The table name, if left empty and $this->_schema is defined, it will use the first table on it.
	 * @return mixed The new inserted ID.
	 */
	public function create($data, $table = '')
	{
		global $smcFunc;

		// What table should be updated?  defaults to $this->_schema[0] if no table was specified.
		$table = isset($this->_schema[$table]) ? $table : (isset($this->_schema[0]) ? key($this->_schema) : '');

		// We need to make sure you defined this table's columns.
		if (empty($this->_schema[$table]))
			return false;

		// Now check the data, perhaps I should check that the columns does match the columns declared for this table but i'm lazy...
		if (empty($data))
			return false;

		// Insert!
		$smcFunc['db_insert']('replace', '{db_prefix}' . ($table) .
			'', $this->_schema[$table], $data, $this->_app->_tableKeys);

		// Get the newly created ID
		$newID = $smcFunc['db_insert_id']('{db_prefix}' . ($table), key($this->_schema[$table]));

		// Return the newly inserted ID.
		return $newID;
	}

	public function read()
	{

	}

	/**
	 * Updates data in x table.
	 * Uses {@link $_schema}
	 * @access public
	 * @param array $ids An array of ids to check againts, can also use an int.
	 * @param array $params An array of params (ohrly???) $column => $value pair.
	 * @param string $table The table name, if left empty and $this->_schema is defined, it will use the first table on it.
	 * @param string $column The column name, if left empty and $this->_schema[$table] is defined, it will use the first column on it.
	 * @return void
	 */
	public function update($ids = array(), $params = array(), $table = '', $column = '')
	{
		global $smcFunc;

		// Basic checks.
		if (!$this->_schema || (empty($params) || !is_array($params)) || empty($ids))
			return false;

		// Work with arrays.
		$ids = (array) $ids;

		// What table should be updated?  defaults to $this->_schema[0] if no table was specified.
		$table = isset($this->_schema[$table]) ? $table : (isset($this->_schema[0]) ? key($this->_schema) : $table);

		// At this point there should be something, perhaps its a vanilla table or a typo or a non-existant table. If theres something, use it.
		if (empty($table))
			return false;

		// Now check the column, if there isn't one, try using the first column in $table.
		if (empty($column) && isset($table[$column]))
			$column = key($table[$column]);

		// Again, at this point we need something, anything...
		if (empty($column))
			return false;

		// Create a nice formatted string.
		$string = '';

		foreach ($params as $column => $newValue)
			$string .= $column .' = '. $newValue . ($newValue != end($params) ? ', ' : '');

		return $smcFunc['db_query']('', '
			UPDATE {db_prefix}' . ($table) . '
			SET '. ($string) .'
			WHERE '. ($column) .' IN ({array_'. ($table[$column]) .':ids})',
			array(
				'ids' => $ids,
			)
		);
	}

	public function delete($value, $table = '', $column = '')
	{
		global $smcFunc;

		if (empty($id) || empty($table) || empty($column))
			return false;

		// Perform.
		return $smcFunc['db_query']('', '
			DELETE FROM {db_prefix}' . ($table) . '
			WHERE '. ($column) .' = '. ($value) .'', array());
	}
}
