<?php
App::uses('Logger', 'Auditable.Model');
App::uses('ModelBehavior', 'Model');
App::uses('AuditableConfig', 'Auditable.Lib');

if(!class_exists('User'))
{
	class User extends CakeTestModel
	{
		public $callbackData = array();

		public $actsAs = array(
			'Auditable.Auditable'
		);

		public $useTable = 'users';

		public $name = 'User';
	}
}

if(!class_exists('User2'))
{
	class User2 extends CakeTestModel
	{
		public $callbackData = array();

		public $actsAs = array(
			'Auditable.Auditable' => array(
				'auditSql' => false,
				'skip' => array(
					'modified'
				)
			)
		);

		public $useTable = 'users';

		public $name = 'User2';
	}
}

if(!class_exists('User3'))
{
	class User3 extends CakeTestModel
	{
		public $callbackData = array();

		public $actsAs = array(
			'Auditable.Auditable' => 'wrong'
		);

		public $useTable = 'users';

		public $name = 'User3';
	}
}

class AuditableTest extends CakeTestCase {
	public $fixtures = array(
		'plugin.Auditable.logger',
		'plugin.Auditable.log_detail',
		'plugin.Auditable.user'
	);

	public $Model = null;


	public function startTest()
	{
		AuditableConfig::$responsibleId = 0;
		AuditableConfig::$responsibleModel = 'User';

		$this->Model = Classregistry::init('User');
	}

	public function endTest()
	{
		unset($this->Model);
		ClassRegistry::flush();
	}

	public function testBehaviorInstance()
	{
		$this->assertTrue(is_a($this->Model->Behaviors->Auditable, 'AuditableBehavior'));
	}

	public function testCustomSettings()
	{
		$User2 = Classregistry::init('User2');
		$result = $User2->getAuditableSettings();
		$expected = array(
			'auditSql' => false,
			'skip' => array(
				'modified'
			),
			'fields' => array(
				'created' => 'created_by',
				'modified' => 'modified_by'
			)
		);

		$this->assertEqual($expected, $result);

		$User3 = Classregistry::init('User3');
		$result = $User3->getAuditableSettings();
		$expected = array(
			'auditSql' => true,
			'skip' => array(
				'created',
				'modified'
			),
			'fields' => array(
				'created' => 'created_by',
				'modified' => 'modified_by'
			),
		);

		$this->assertEqual($expected, $result);
	}
}