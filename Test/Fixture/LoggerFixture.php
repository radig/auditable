<?php
/**
 * LoggerFixture
 *
 * @package auditable
 * @subpackage auditable.tests.fixtures
 */
class LoggerFixture extends CakeTestFixture {

	/**
	 * Name
	 *
	 * @var string $name
	 */
	public $name = 'Logger';

	/**
	 * Table
	 *
	 * @var array $table
	 */
	public $table = 'logs';

	/**
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'length' => 11, 'key' => 'primary'),
		'responsible_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
		'model_alias' => array('type'=>'string', 'null' => false, 'default' => NULL),
		'model_id' => array('type'=>'integer', 'null' => false, 'default' => NULL),
		'log_detail_id' => array('type'=>'integer', 'null' => false, 'default' => NULL),
		'type' => array('type'=>'integer', 'null' => true, 'default' => NULL),
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
			'responsible_id'  => '0',
			'model_alias' => 'User',
			'model_id' => '1',
			'log_detail_id' => '1',
			'type' => '1',
			'created'  => '2012-03-08 15:20:10',
			'modified'  => '2012-03-08 15:20:10'
		)
	);
}