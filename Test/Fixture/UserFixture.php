<?php
/**
 * UserFixture
 *
 * @package auditable
 * @subpackage auditable.tests.fixtures
 */
class UserFixture extends CakeTestFixture {

	/**
	 * Name
	 *
	 * @var string $name
	 */
	public $name = 'User';

	/**
	 * Table
	 *
	 * @var array $table
	 */
	public $table = 'users';

	/**
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'length' => 11, 'key' => 'primary'),
		'username' => array('type'=>'string', 'null' => false, 'default' => null),
		'email' => array('type'=>'string', 'null' => true, 'default' => null),
		'created' => array('type'=>'datetime', 'null' => true, 'default' => null),
		'modified' => array('type'=>'datetime', 'null' => true, 'default' => null),
		'created_by' => array('type' => 'integer', 'null' => true, 'default' => null),
		'modified_by' => array('type' => 'integer', 'null' => true, 'default' => null),
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
			'username'  => 'userA',
			'email' => 'user_a@radig.com.br',
			'created'  => '2012-03-08 15:20:10',
			'modified'  => '2012-03-08 15:20:10',
			'created_by' => null,
			'modified_by' => null,
		),
		array(
			'id'  => '2',
			'username'  => 'userB',
			'email' => 'user_b@radig.com.br',
			'created'  => '2012-03-08 15:22:26',
			'modified'  => '2012-03-08 15:25:38',
			'created_by' => null,
			'modified_by' => null,
		)
	);
}