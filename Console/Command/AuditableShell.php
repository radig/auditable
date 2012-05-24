<?php
/**
 * CakePHP Auditable
 *
 * Copyright 2011 - 2012, Radig Soluções em TI
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2011 - 2012, Radig Soluções em TI
 * @link      http://github.com/radig/auditable
 * @package   Plugn.Auditable
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Shell', 'Console');
App::uses('ConnectionManager', 'Model');
App::uses('CakeSchema', 'Model');

/**
 * Auditable Shell.
 *
 * @package       Auditable
 * @subpackage    Auditable.Console.Commands
 */
class AuditableShell extends Shell {

/**
 * Connection used
 *
 * @var string
 */
	public $connection = 'default';

/**
 * DataSource instance
 * @var DataSource
 */
	public $db = null;

/**
 *
 * @var array
 */
	public $ignoredModels = array(
		'Log',
		'LogDetail',
	);

	public $ignoredTables = array(
		'acos',
		'aros',
		'aros_acos',
		'logs',
		'sessions',
		'schema_migrations',
	);

/**
 * Override startup
 *
 * @return void
 */
	public function startup()
	{
		$this->out(__d('Auditable', 'Cake Auditable Shell'));
		$this->hr();

		if (!empty($this->params['connection']))
			$this->connection = $this->params['connection'];
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		return $parser->description(
			'The Auditable shell.' .
			'')
			->addOption('connection', array(
					'short' => 'c',
					'default' => 'default',
					'help' => __d('Auditable', 'Set db config <config>. Uses \'default\' if none is specified.')))
			->addOption('force', array(
					'short' => 'f',
					'boolean' => true,
					'help' => __d('Auditable', 'Force changes in all tables of database.')))
			->addSubcommand('insert', array(
				'help' => __d('Auditable', 'Insert columns \'created_by\' and \'modified_by\' in all database tables.')))
			->addSubcommand('remove', array(
				'help' => __d('Auditable', 'Remove columns \'created_by\' and \'modified_by\' from all database tables.')));
	}

/**
 * Override main
 *
 * @return void
 */
	public function main()
	{
		$this->run();
	}

/**
 * Run
 *
 * @return void
 */
	public function run()
	{
		$null = null;
		$this->db =& ConnectionManager::getDataSource($this->connection);
		$this->db->cacheSources = false;
		$this->db->begin($null);

		if(!isset($this->args[0]) || !in_array($this->args[0], array('insert', 'remove')))
		{
			$this->out(__d('Auditable', 'Invalid option'));
			return $this->_displayHelp(null);
		}

		try {
			$this->_run($this->args[0]);
			$this->_clearCache();

		} catch (Exception $e) {
			$this->db->rollback($null);
			throw $e;
		}

		return $this->db->commit($null);

		$this->out(__d('Auditable', 'All tables are updated.'));
		$this->out('');
		return true;
	}

	protected function _run($type)
	{
		if(!isset($this->params['force']))
			$tables = $this->_getModels();
		else
			$tables = $this->_getTables();

		if($type === 'insert')
			$this->out(__d('Auditable', 'Adding fields'));
		else
			$this->out(__d('Auditable', 'Droping fields'));

		$this->out('');

		foreach($tables as $tableName => $schema)
		{

			$status = $this->{'_' . $type}($schema, $tableName);

			if($status !== null)
			{
				$this->out(sprintf(__d('Auditable', 'Changing table \'%s\': %s'), $tableName, $status ? __d('Auditable', 'Success') : __d('Auditable', 'Error')));
			}
		}
	}

	protected function _insert($schema, $tableName)
	{
		$fieldOptions = array('type' => 'integer', 'null' => true, 'default' => null);

		$changes = array('add' => array());

		if(!isset($schema['created_by']))
			$changes[$tableName]['add']['created_by'] = $fieldOptions;

		if(!isset($schema['modified_by']))
			$changes[$tableName]['add']['modified_by'] = $fieldOptions;

		$sql = $this->db->alterSchema($changes);

		if(empty($sql))
			return null;

		return (bool)$this->_execute($sql);
	}

	protected function _remove($schema, $tableName)
	{
		$changes = array('drop' => array());

		if(isset($schema['created_by']))
			$changes[$tableName]['drop']['created_by'] = array();

		if(isset($schema['modified_by']))
			$changes[$tableName]['drop']['modified_by'] = array();

		$sql = $this->db->alterSchema($changes);

		if(empty($sql))
			return null;

		return (bool)$this->_execute($sql);
	}

	protected function _execute($sql)
	{
		if (@$this->db->execute($sql) === false)
			throw new Exception($this, sprintf(__d('Auditable', 'SQL Error: %s'), $this->db->lastError()));

		return true;
	}

	protected function _getModels()
	{
		$models = App::objects('Model');
		$plugins = CakePlugin::loaded();

		foreach($plugins as $plugin)
		{
			$pluginModels = App::objects($plugin . '.Model');

			if(empty($pluginModels))
				continue;

			foreach ($pluginModels as $model)
			{
				if(in_array($model, $this->ignoredModels))
					continue;

				$models[] = $plugin . '.' . $model;
			}
		}

		$out = array();
		foreach($models as $k => $m)
		{
			if(strpos(strtolower($m), 'appmodel') !== false)
				continue;

			$_model = ClassRegistry::init($m);

			if(!isset($_model->table) || empty($_model->table) || in_array($_model->table, $this->ignoredModels) || $_model->useDbConfig !== $this->connection)
				continue;

			$schema = $_model->schema(true);

			if(empty($schema))
				continue;

			$tableName = $_model->tablePrefix ? $_model->tablePrefix . $_model->table : $_model->table;

			$out[$tableName] = $schema;
		}

		return $out;
	}

	protected function _getTables()
	{
		$_Schema = new CakeSchema();
		$database = $_Schema->read();

		$tables = array();
		foreach($database['tables'] as $tableName => $schema)
		{
			if(in_array($tableName, $this->ignoredTables) || empty($tableName) || $tableName === 'missing')
				continue;

			$tables[$tableName] = $schema;
		}

		return $tables;
	}

	protected function _clearCache() {
		DboSource::$methodCache = array();
		$keys = Cache::configured();
		foreach ($keys as $key) {
			Cache::clear(false, $key);
		}
		ClassRegistry::flush();
	}
}
