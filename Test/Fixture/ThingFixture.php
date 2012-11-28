<?php
/**
 * ThingFixture
 *
 * @package auditable
 * @subpackage auditable.tests.fixtures
 */
class ThingFixture extends CakeTestFixture {

	/**
	 * Name
	 *
	 * @var string $name
	 */
	public $name = 'Thing';

	/**
	 * Table
	 *
	 * @var array $table
	 */
	public $table = 'things';

	/**
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type'=>'string', 'null' => false),
		'user_id' => array('type'=>'integer', 'null' => true)
	);

	/**
	 * @var array
	 */
	public $records = array(
		array(
			'id'  => '1',
			'name'  => 'ThingOne',
			'user_id' => '2',
		),
		array(
			'id'  => '2',
			'name'  => 'ThingTwo',
			'user_id' => '1'
		)
	);
}