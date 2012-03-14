<?php
App::uses('AuditableConfig', 'Auditable.Lib');
class LoggerTest extends CakeTestCase {
	public $Logger = null;

	public $plugin = 'Auditable';

	public $fixtures = array(
		'plugin.auditable.logger',
		'plugin.auditable.log_detail',
		'plugin.auditable.user'
	);

	public function setUp()
	{
		$this->Logger = ClassRegistry::init('Auditable.Logger');
	}

	public function tearDown()
	{
		parent::tearDown();
		unset($this->Logger);
		ClassRegistry::flush();
	}

	public function testLoggerInstance()
	{
		$this->assertTrue(is_a($this->Logger, 'Logger'));
	}

	public function testWriteLog()
	{
		$toSave = array(
			'Logger' => array(
				'responsible_id' => 0,
				'model_alias' => 'Teste',
				'model_id' => 1,
				'type' => 1,
			),
			'LogDetail' => array(
				'difference' => '{}',
				'statement' => 'UPDATE',
			)
		);

		$result = $this->Logger->save($toSave);
	}

	public function testReadLog()
	{
		$this->Logger->recursive = -1;
		$result = $this->Logger->read(null, 1);
		$expected = array(
			'Logger' => array(
				'id'  => 1,
				'responsible_id'  => 0,
				'model_alias' => 'User',
				'model_id' => 1,
				'log_detail_id' => 1,
				'type' => 1,
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			)
		);

		$this->assertEqual($result, $expected);

		$this->Logger->recursive = 0;
		$result = $this->Logger->read(null, 1);
		$expected = array(
			'Logger' => array(
				'id'  => 1,
				'responsible_id'  => 0,
				'model_alias' => 'User',
				'model_id' => 1,
				'log_detail_id' => 1,
				'type' => 1,
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'LogDetail' => array(
				'id' => 1,
				'difference' => '{}',
				'statement' => '',
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			)
		);

		$this->assertEqual($result, $expected);
	}

	public function testGetWithoutResourceLog()
	{
		$result = $this->Logger->get(1, false);

		$expected = array(
			'Logger' => array(
				'id'  => 1,
				'responsible_id' => 0,
				'model_alias' => 'User',
				'model_id' => 1,
				'log_detail_id' => 1,
				'type' => 1,
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'LogDetail' => array(
				'id' => 1,
				'difference' => '{}',
				'statement' => '',
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'Responsible' => array(
				'id' => null,
				'username' => null,
				'email' => null,
				'created' => null,
				'modified' => null,
				'created_by' => null,
				'modified_by' => null
			)
		);

		$this->assertEqual($result, $expected);

		AuditableConfig::$responsibleModel = null;
		$result = $this->Logger->get(1, false);

		$expected = array(
			'Logger' => array(
				'id'  => 1,
				'responsible_id'  => 0,
				'model_alias' => 'User',
				'model_id' => 1,
				'log_detail_id' => 1,
				'type' => 1,
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'LogDetail' => array(
				'id' => 1,
				'difference' => '{}',
				'statement' => '',
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'Responsible' => array(
				'name' => ''
			)
		);

		$this->assertEqual($result, $expected);

		AuditableConfig::$responsibleModel = 'User';
	}

	public function testGetWithResourceLog()
	{
		$result = $this->Logger->get(1);

		$expected = array(
			'Logger' => array(
				'id'  => 1,
				'responsible_id'  => 0,
				'model_alias' => 'User',
				'model_id' => 1,
				'log_detail_id' => 1,
				'type' => 1,
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'LogDetail' => array(
				'id' => 1,
				'difference' => '{}',
				'statement' => '',
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10'
			),
			'Responsible' => array(
				'id' => null,
				'username' => null,
				'email' => null,
				'created' => null,
				'modified' => null,
				'created_by' => null,
				'modified_by' => null
			),
			'User' => array(
				'id'  => 1,
				'username'  => 'userA',
				'email' => 'user_a@radig.com.br',
				'created'  => '2012-03-08 15:20:10',
				'modified'  => '2012-03-08 15:20:10',
				'created_by' => null,
				'modified_by' => null
			)
		);

		$this->assertEqual($result, $expected);
	}
}