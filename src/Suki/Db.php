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
	 * Creates a new entry on x table.
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

	/**
	 * Reads data from a table.
	 *
	 * @param array $params An array with all the params  for the query
	 * @param array $data An array to pass to $smcFunc casting array
	 * @param bool $key A boolean value to asign a row as key on the returning array
	 * @param bool $single A bool to tell the query to return a single value instead of An array
	 * @return mixed either An array or a var with the query result
	 */
	public function read($params, $data, $key = false, $single = false)
	{
		global $smcFunc;
		$dataResult = array();
		$query = $smcFunc['db_query']('', '
			SELECT ' . $params['rows'] .'
			FROM {db_prefix}' . $params['table'] .'
			'. (!empty($params['join']) ? 'LEFT JOIN '. $params['join'] : '') .'
			'. (!empty($params['where']) ? 'WHERE '. $params['where'] : '') .'
				'. (!empty($params['and']) ? 'AND '. $params['and'] : '') .'
				'. (!empty($params['andTwo']) ? 'AND '. $params['andTwo'] : '') .'
			'. (!empty($params['order']) ? 'ORDER BY ' . $params['order'] : '') .'
			'. (!empty($params['limit']) ? 'LIMIT '. $params['limit'] : '') . '',
			$data
		);

		if (!empty($single))
			while ($row = $smcFunc['db_fetch_assoc']($query))
				$dataResult = $row;

		elseif (!empty($key) && empty($single))
			while ($row = $smcFunc['db_fetch_assoc']($query))
				$dataResult[$row[$key]] = $row;

		elseif (empty($single) && empty($key))
			while ($row = $smcFunc['db_fetch_assoc']($query))
				$dataResult[] = $row;

		$smcFunc['db_free_result']($query);

		return $dataResult;
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

	/**
	 * Deletes an entry from X table.
	 * @access public
	 * @param mixed
	 * @param string $table The table name.
	 * @param string $table The table name, if left empty and $this->_schema is defined, it will use the first table on it.
	 * @param string $column The column name, if left empty and $this->_schema[$table] is defined, it will use the first column on it.
	 * @return void
	 */
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
