<?php
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

if(!class_exists('Thing'))
{
	class Thing extends CakeTestModel
	{
		public $callbackData = array();

		public $actsAs = array(
			'Auditable.Auditable'
		);

		public $useTable = 'things';

		public $name = 'Thing';
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
		'plugin.Auditable.user',
		'plugin.Auditable.thing'
	);

	public $Model = null;

	public $Logger = null;

	protected $_isNoSQL = false;

	public function setUp()
	{
		AuditableConfig::$responsibleId = 0;
		AuditableConfig::$responsibleModel = 'User';

		$this->Model = Classregistry::init('User');

		if(!is_a($this->Model->getDataSource(), 'MongodbSource')) {
			App::uses('Logger', 'Auditable.Model');
			$this->Logger = Classregistry::init('Auditable.Logger');
		} else {
			$this->_isNoSQL = true;
			App::uses('Logger', 'AuditableMongoLogger.Model');
			$this->Logger = Classregistry::init('AuditableMongoLogger.Logger');
		}

		AuditableConfig::$Logger = $this->Logger;
	}

	public function startTest($method) {
		/**
		 * @hack para corrigir o valor da sequência ligada a chave primária quando utilizando Postgres
		 */
		if(is_a($this->Logger->getDataSource(), 'Postgres')) {
			$ds = $this->Logger->getDataSource();

			// logger fixture
			$sequence = $ds->value($ds->getSequence($this->Logger->useTable, 'id'));
			$table = $ds->fullTableName($this->Logger->useTable);
			$ds->execute("SELECT setval({$sequence}, (SELECT MAX(id) FROM {$table}))");

			// log_detail fixture
			$sequence = $ds->value($ds->getSequence($this->Logger->LogDetail->useTable, 'id'));
			$table = $ds->fullTableName($this->Logger->LogDetail->useTable);
			$ds->execute("SELECT setval({$sequence}, (SELECT MAX(id) FROM {$table}))");

			// user fixture
			$sequence = $ds->value($ds->getSequence($this->Model->useTable, 'id'));
			$table = $ds->fullTableName($this->Model->useTable);
			$ds->execute("SELECT setval({$sequence}, (SELECT MAX(id) FROM {$table}))");

			// thing fixture
			$sequence = $ds->value($ds->getSequence('things', 'id'));
			$table = $ds->fullTableName('things');
			$ds->execute("SELECT setval({$sequence}, (SELECT MAX(id) FROM {$table}))");
		}
	}

	public function tearDown()
	{
		/*
		 * Caso esteja usando Mongodb,
		 * o Datasource não suporta completamente Fixture,
		 * é preciso apagar os dados manualmente
		 */
		if($this->_isNoSQL) {
			$this->Model->deleteAll(true, false);
			$this->Logger->deleteAll(true, false);
		}

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

		$log = $this->Logger->find('first', array('order' => array("Logger.{$this->Logger->primaryKey}" => 'desc'), 'fields' => array("Logger.{$this->Logger->primaryKey}")));

		$result = $this->Logger->get($log['Logger'][$this->Logger->primaryKey]);

		// ignora dados temporais não relevantes e o id que pode ser hash
		unset($result['Logger']['created'], $result['Logger']['modified'], $result['Logger'][$this->Logger->primaryKey]);

		$expected = array(
			'responsible_id' => 0,
			'model_alias' => 'User',
			'model_id' => $this->Model->id,
			'log_detail_id' => $this->Logger->LogDetail->id,
			'type' => 1
		);

		$this->assertEqual($result['Logger'], $expected, 'Registro principal do log está incorreto');

		$difference = call_user_func(AuditableConfig::$serialize, array('username' => 'dotti', 'email' => 'dotti@radig.com.br', 'id' => $this->Model->id));

		// checa a diferança
		$this->assertEqual($result['LogDetail']['difference'], $difference, 'Diferença do log está incorreta');

		/*
		 * @fixme Alguma alteração no CakePHP/DboSource quebrou o teste abaixo,
		 * para corrigí-lo é preciso refatorar a lib QueryLogSource
		 */
		// checa statement
		//$this->assertRegExp('/^INSERT INTO .+ \((`|\")username(`|\")\, (`|\")email(`|\")\, (`|\")modified(`|\")\, (`|\")created(`|\")\) VALUES \(\'dotti\'\, \'dotti@radig.com.br\'.*/', $result['LogDetail']['statement'], 'Statement SQL está incorreto');

		// checa o responsável
		$this->assertEqual($result['Responsible'], array());

		unset($result['User']['created'], $result['User']['modified']);

		$expected = array('username' => 'dotti', 'email' => 'dotti@radig.com.br', 'id' => $this->Model->id, 'created_by' => null, 'modified_by' => null);

		if($this->_isNoSQL) {
			unset($expected['modified_by'], $expected['created_by']);
		}

		// checa o "resource"
		$this->assertEqual($result['User'], $expected);
	}

	public function testAddRelatedAuditable()
	{
		$this->Model->bindModel(array('hasOne' => array('Thing')), false);

		$this->Model->saveAssociated(
			array(
				'User' => array(
					'username' => 'dotti',
					'email' => 'dotti@radig.com.br'
				),
				'Thing' => array(
					'name' => 'Another'
				)
			)
		);

		$log = $this->Logger->find('all', array(
			'order' => array("Logger.{$this->Logger->primaryKey}" => 'desc'),
			'fields' => array("Logger.{$this->Logger->primaryKey}"))
		);

		$result1 = $this->Logger->get($log[0]['Logger'][$this->Logger->primaryKey]); // model Thing
		$result2 = $this->Logger->get($log[1]['Logger'][$this->Logger->primaryKey]); // model User

		$logDetailId = $this->Logger->LogDetail->find('first', array(
			'order' => array("LogDetail.{$this->Logger->LogDetail->primaryKey}" => 'desc'),
			'fields' => array("LogDetail.{$this->Logger->LogDetail->primaryKey}")
			)
		);

		// ignora dados temporais não relevantes
		unset($result1['Logger']['created'], $result1['Logger']['modified'], $result1['Logger'][$this->Logger->primaryKey]);
		unset($result2['Logger']['created'], $result2['Logger']['modified'], $result2['Logger'][$this->Logger->primaryKey]);

		$expected = array(
			'responsible_id' => 0,
			'model_alias' => 'Thing',
			'model_id' => $this->Model->Thing->id,
			'log_detail_id' => $logDetailId['LogDetail'][$this->Logger->LogDetail->primaryKey],
			'type' => 1
		);

		$this->assertEqual($result1['Logger'], $expected);

		$expected = array(
			'name' => 'Another',
			'id' => $this->Model->Thing->id,
			'user_id' => $this->Model->id
		);

		// checa o "resource"
		$this->assertEqual($result1['Thing'], $expected);
	}

	public function testLogResponsible()
	{
		AuditableConfig::$responsibleId = 1;

		$this->Model->save(array(
			'username' => 'radig',
			'email' => 'teste@radig.com.br'
			)
		);

		$log = $this->Logger->find('first', array('order' => array("Logger.{$this->Logger->primaryKey}" => 'desc'), 'fields' => array("Logger.{$this->Logger->primaryKey}")));

		$result = $this->Logger->get($log['Logger'][$this->Logger->primaryKey]);

		// checa o responsável
		$this->assertEqual($result['Responsible'], array(
			'id' => '1',
			'username' => 'userA',
			'email' => 'user_a@radig.com.br',
			'created' => '2012-03-08 15:20:10',
			'modified' => '2012-03-08 15:20:10',
			'created_by' => null,
			'modified_by' => null
			)
		);
	}

	public function testDelete()
	{
		$this->Model->delete(2);

		$log = $this->Logger->find('first', array('order' => array("Logger.{$this->Logger->primaryKey}" => 'desc'), 'fields' => array("Logger.{$this->Logger->primaryKey}")));

		$result = $this->Logger->get($log['Logger'][$this->Logger->primaryKey]);

		$this->assertEqual($result['Logger']['type'], '3');

		if(!$this->_isNoSQL) {
			$this->assertRegExp('/^DELETE.*/', $result['LogDetail']['statement']);
		}
	}

	public function testMofify()
	{
		AuditableConfig::$responsibleId = 2;

		$this->Model->save(array(
			'id' => 1,
			'username' => 'userChanged'
			)
		);

		$log = $this->Logger->find('first', array('order' => array("Logger.{$this->Logger->primaryKey}" => 'desc'), 'fields' => array("Logger.{$this->Logger->primaryKey}")));

		$result = $this->Logger->get($log['Logger'][$this->Logger->primaryKey]);

		$this->assertEqual($result['Logger']['type'], '2');

		$difference = call_user_func(AuditableConfig::$serialize, array('username' => array('old' => 'userA', 'new' => 'userChanged')));
		$this->assertEqual($result['LogDetail']['difference'], $difference);

		if(!$this->_isNoSQL) {
			$this->assertRegExp('/^UPDATE.*/', $result['LogDetail']['statement']);
		}
	}

	public function testSetters()
	{
		$this->Model->setLogger($this->Logger);

		$this->Model->setActiveResponsible(2);

		$settings = $this->Model->getAuditableSettings(false);

		$this->assertSame($this->Logger, $settings['Logger']);

		$this->assertEqual($settings['activeResponsibleId'], 2);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testUsingInvalidModel()
	{
		$this->Model->setLogger(new Object());

		$this->Model->save(array('username' => 'lala', 'email' => 'lala@radig.com.br'));

		$count = $this->Logger->find('count');

		// Só contém 1 registro do fixture, nenhum log gerado
		$this->assertEqual($count, 1);
	}
}
