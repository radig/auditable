<?php
/**
 * LogDetailFixture
 *
 * @package auditable
 * @subpackage auditable.tests.fixtures
 */
class LogDetailFixture extends CakeTestFixture {

	/**
	 * Name
	 *
	 * @var string $name
	 */
	public $name = 'LogDetail';

	/**
	 * Table
	 *
	 * @var array $table
	 */
	public $table = 'log_details';

	/**
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'length' => 11, 'key' => 'primary'),
		'difference' => array('type'=>'text', 'null' => true, 'default' => NULL),
		'statement' => array('type'=>'text', 'null' => false, 'default' => NULL),
		'created' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		)
	);

	/**
	 * @var array
	 */
	public $records = array(
		array(
			'id'  => '1',
			'difference' => '{}',
			'statement' => '',
			'created'  => '2012-03-08 15:20:10',
			'modified'  => '2012-03-08 15:20:10'
		)
	);
}