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
		'Aro',
		'Aco',
	);

	public $ignoredTables = array(
		'logs',
		'sessions',
		'aros_acos',
		'schema_migrations',
	);

/**
 * Override startup
 *
 * @return void
 */
	public function startup() {
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
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			'The Auditable shell.' .
			'')
			->addOption('connection', array(
					'short' => 'c',
					'default' => 'default',
					'help' => __d('Auditable', 'Set db config <config>. Uses \'default\' if none is specified.')))
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
	public function main() {
		$this->run();
	}

/**
 * Run
 *
 * @return void
 */
	public function run() {
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
			if($this->args[0] === 'insert')
				$this->_insert();

			if($this->args[0] === 'remove')
				$this->_remove();

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

	protected function _insert() {
		$fieldOptions = array('type' => 'integer', 'null' => true, 'default' => null);
		$models = $this->_getModels();

		$this->out(__d('Auditable', 'Adding fields'));
		$this->out('');

		foreach($models as $modelName)
		{
			$_model = ClassRegistry::init($modelName);

			if(!isset($_model->table) || empty($_model->table) || in_array($_model->table, $this->ignoredTables) || $_model->useDbConfig !== $this->connection)
				continue;

			$schema = $_model->schema(true);

			if(empty($schema))
				continue;

			$tableName = $_model->tablePrefix ? $_model->tablePrefix . $_model->table : $_model->table;

			$changes = array('add' => array());

			if(!isset($schema['created_by']))
				$changes[$tableName]['add']['created_by'] = $fieldOptions;

			if(!isset($schema['modified_by']))
				$changes[$tableName]['add']['modified_by'] = $fieldOptions;

			$sql = $this->db->alterSchema($changes);

			if(empty($sql))
				continue;

			$status = $this->_execute($sql) ? 'Sucesso' : 'Falha';

			$this->out(sprintf(__d('Auditable', 'Changing table \'%s\': %s'), $tableName, $status));
		}
	}

	protected function _remove()
	{
		$models = $this->_getModels();

		$this->out(__d('Auditable', 'Droping fields'));
		$this->out('');

		foreach($models as $modelName)
		{
			$_model = ClassRegistry::init($modelName);

			if(!isset($_model->table) || empty($_model->table) || in_array($_model->table, $this->ignoredTables) || $_model->useDbConfig !== $this->connection)
				continue;

			$schema = $_model->schema(true);

			if(empty($schema))
				continue;

			$tableName = $_model->tablePrefix ? $_model->tablePrefix . $_model->table : $_model->table;

			$changes = array('drop' => array());

			if(isset($schema['created_by']))
				$changes[$tableName]['drop']['created_by'] = array();

			if(isset($schema['modified_by']))
				$changes[$tableName]['drop']['modified_by'] = array();

			$sql = $this->db->alterSchema($changes);

			if(empty($sql))
				continue;

			$status = $this->_execute($sql) ? 'Sucesso' : 'Falha';

			$this->out(sprintf(__d('Auditable', 'Changing table \'%s\': %s'), $tableName, $status));
		}

		return true;
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

		foreach($models as $k => $m)
		{
			if(strpos(strtolower($m), 'appmodel') !== false)
				unset($models[$k]);
		}

		return $models;
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
