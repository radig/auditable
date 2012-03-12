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
				'priority' => 1,
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

	public $Logger = null;


	public function startTest()
	{
		AuditableConfig::$responsibleId = 0;
		AuditableConfig::$responsibleModel = 'User';

		$this->Model =& Classregistry::init('User');
		$this->Logger =& Classregistry::init('Auditable.Logger');

		AuditableConfig::$Logger =& $this->Logger;
	}

	public function endTest()
	{
		unset($this->Model);
		unset($this->Logger);
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
			'priority' => 1,
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
			'priority' => 1,
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

	public function testAddAuditable()
	{
		$this->Model->save(array(
			'username' => 'dotti',
			'email' => 'dotti@radig.com.br'
			)
		);

		$log = $this->Logger->find('first', array('order' => array('id' => 'desc'), 'fields' => array('id')));

		$result = $this->Logger->get($log['Logger']['id']);

		// ignora dados temporais não relevantes
		unset($result['Logger']['created'], $result['Logger']['modified']);

		$expected = array(
			'id' => $log['Logger']['id'],
			'responsible_id' => 0,
			'model_alias' => 'User',
			'model_id' => 3,
			'log_detail_id' => 2,
			'type' => 1
		);

		$this->assertEqual($result['Logger'], $expected);

		$difference = call_user_func(AuditableConfig::$serialize, array('username' => 'dotti', 'email' => 'dotti@radig.com.br', 'id' => '3'));

		// checa a diferança
		$this->assertEqual($result['LogDetail']['difference'], $difference);

		// checa statement
		$this->assertRegExp('/^INSERT INTO .+ \(`username`\, `email`\, `modified`\, `created`\) VALUES \(\'dotti\'\, \'dotti@radig.com.br\'.*/', $result['LogDetail']['statement']);

		// checa o responsável
		$this->assertEqual($result['Responsible'], array('id' => null, 'username' => null, 'email' => null, 'created' => null, 'modified' => null, 'created_by' => null, 'modified_by' => null));

		unset($result['User']['created'], $result['User']['modified']);

		// checa o "resource"
		$this->assertEqual($result['User'], array('username' => 'dotti', 'email' => 'dotti@radig.com.br', 'id' => '3', 'created_by' => null, 'modified_by' => null));
	}

	public function testLogResponsible()
	{
		AuditableConfig::$responsibleId = 1;

		$this->Model->save(array(
			'username' => 'radig',
			'email' => 'teste@radig.com.br'
			)
		);

		$log = $this->Logger->find('first', array('order' => array('id' => 'desc'), 'fields' => array('id')));

		$result = $this->Logger->get($log['Logger']['id']);

		// checa o responsável
		$this->assertEqual($result['Responsible'], array('id' => '1', 'username' => 'userA', 'email' => 'user_a@radig.com.br', 'created' => '2012-03-08 15:20:10', 'modified' => '2012-03-08 15:20:10', 'created_by' => null, 'modified_by' => null));
	}

	public function testDelete()
	{
		$this->Model->delete(2);

		$log = $this->Logger->find('first', array('order' => array('id' => 'desc'), 'fields' => array('id')));

		$result = $this->Logger->get($log['Logger']['id']);

		$this->assertEqual($result['Logger']['type'], '3');

		$this->assertRegExp('/^DELETE.*/', $result['LogDetail']['statement']);
	}

	public function testMofify()
	{
		AuditableConfig::$responsibleId = 2;

		$this->Model->save(array(
			'id' => 1,
			'username' => 'userChanged'
			)
		);

		$log = $this->Logger->find('first', array('order' => array('id' => 'desc'), 'fields' => array('id')));

		$result = $this->Logger->get($log['Logger']['id']);

		$this->assertEqual($result['Logger']['type'], '2');

		$difference = call_user_func(AuditableConfig::$serialize, array('username' => array('old' => 'userA', 'new' => 'userChanged')));
		$this->assertEqual($result['LogDetail']['difference'], $difference);

		$this->assertRegExp('/^UPDATE.*/', $result['LogDetail']['statement']);
	}

	public function testSetters()
	{
		$this->Model->setLogger($this->Logger);

		$this->Model->setActiveResponsible(2);

		$settings = $this->Model->getAuditableSettings(false);

		$this->assertSame($this->Logger, $settings['Logger']);

		$this->assertEqual($settings['activeResponsibleId'], 2);
	}

	public function testUsingInvalidModel()
	{
		$this->Model->setLogger(new Object());

		$this->Model->save(array('username' => 'lala', 'email' => 'lala@radig.com.br'));

		$count = $this->Logger->find('count');

		// Só contém 1 registro do fixture, nenhum log gerado
		$this->assertEqual($count, 1);
	}
}