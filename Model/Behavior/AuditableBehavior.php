<?php
/**
 * Behavior to automagic log actions in aplications with authenticated users.
 * Store info about user which created entry, and user which modified an entry at the proper
 * table.
 * Also use a table called 'logs' (or other configurated for that) to save created/edited action
 * for continuous changes history.
 * 
 * Code comments in brazilian portuguese.
 * -----
 * 
 * Behavior que permite log automágico de ações executadas em uma aplicação com
 * usuários autenticados.
 * 
 * Armazena informações do usuário que criou um registro, e do usuário
 * que alterou o registro no próprio registro.
 * 
 * Ainda utiliza um modelo auxiliar Logger (ou outro configurado para isso) para registrar
 * ações de inserção/edição e manter um histórico contínuo de alterações.
 * 
 * PHP version > 5.3.1
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @copyright 2011-2012, Radig - Soluções em TI, www.radig.com.br
 * @link http://www.radig.com.br
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @package radig
 * @subpackage Auditable.Model.Behavior
 */

App::uses('Lib', 'Auditable/AuditableConfig');

class AuditableBehavior extends ModelBehavior
{
	/**
	 * Configurações correntes do Behavior
	 * 
	 * @var array
	 */
	public $settings = array();
	
	/**
	 * Configurações padrões do Behavior
	 * 
	 * @var array
	 */
	protected $defaults = array(
		'format' => ':action record with :data',
		'skip' => array(),
		'fields' => array(
			'created' => 'created_by',
			'modified' => 'modified_by'
		)
	);
	
	/**
	 * Dados do usuário ativo, formatado
	 * como um resultado de um find('first')
	 * 
	 * @var array
	 */
	protected $activeUserId = null;
	
	/**
	 * Referência para o modelo que persistirá
	 * as informações
	 * 
	 * @var Model
	 */
	protected $Logger = null;
	
	/**
	 * Guarda o instântaneo do registro antes
	 * de ser atualizado. O valor é usado após
	 * confirmação da alteração no registro e depois
	 * é limpado deste 'cache'
	 * 
	 * @var array
	 */
	protected $snapshots = array();
	
	/**
	 *  
	 */
	public function __construct() {
		parent::__construct();
		
		$this->activeUserId =& AuditableConfig::$userId;
		$this->Logger =& AuditableConfig::$Logger;
	}
	
	/**
	 * Chamado pelo CakePHP para iniciar o behavior
	 *
	 * @param Model $Model
	 * @param array $config
	 * 
	 * Opções de configurações:
	 *   format			: ':action record with :data',
	 *   skip           : array. Lista com nome das ações que devem ser ignoradas pelo log.
	 *   fields			: array. Aceita os dois índices abaixo
	 *     - created	: string. Nome do campo presente em cada modelo para armazenar quem criou o registro
	 *     - modified	: string. Nome do campo presente em cada modelo para armazenar quem alterou o registro
	 */
	public function setup(&$Model, $config = array())
	{
		if (!is_array($config))
		{
			$config = array();
		}
		
		$this->settings[$Model->alias] = array_merge($this->defaults, $config);
	}
	
	/**
	 * Passa referência para modelo responsável por persistir os logs
	 * 
	 * @param Model $Model
	 * @param Logger $L 
	 */
	public function setLogger(&$Model, Logger &$L)
	{
		$this->Logger = $L;
	}
	
	/**
	 * Permite a definição do usuário ativo.
	 * 
	 * @param array $userData
	 */
	public function setActiveUser(&$Model, $userData = array())
	{
		if(empty($userData) || !is_array($userData))
			return false;
		
		$this->activeUserId = $userData;
		
		return true;
	}
	
	/**
	 *
	 * @param Model $Model
	 * @return bool 
	 */
	public function beforeSave(&$Model)
	{
		parent::beforeSave($Model);
		
		$create = empty($Model->data[$Model->alias]['id']);
		
		$this->logResponsible($Model, $create);
		
		$this->takeSnapshot($Model, $create);
		
		return true;
	}
	
	/**
	 *
	 * @param Model $Model
	 * @param bool $created
	 * @return bool 
	 */
	public function afterSave(&$Model, $created)
	{
		parent::afterSave($Model, $created);
		
		$this->logQuery($Model, $created);
		
		return true;
	}


	/**
	 * Recupera as definições para o modelo corrente
	 * 
	 * @param Model $Model
	 */
	public function settings(&$Model)
	{
		return $this->settings[$Model->alias];
	}
	
	/**
	 * Altera dados que estão sendo salvos para incluir responsável pela
	 * ação.
	 * 
	 * @param Model $Model
	 * @param bool $create 
	 */
	protected function logResponsible(&$Model, $create = true)
	{
		$createdByField = $this->settings[$Model->alias]['fields']['created'];
		$modifiedByField = $this->settings[$Model->alias]['fields']['modified'];
		
		if($create && $Model->schema($createdByField) !== null)
		{
			$Model->data[$Model->alias][$createdByField] = $this->activeUserId;
		}
		
		if(!$create && $Model->schema($modifiedByField) !== null)
		{
			$Model->data[$Model->alias][$modifiedByField] = $this->activeUserId;
		}
	}
	
	/**
	 * Guarda um instântaneo do registro que está sendo alterado
	 * 
	 * @param Model $Model
	 * @param bool $create
	 * 
	 * @return void 
	 */
	protected function takeSnapshot(&$Model, $create = true)
	{
		if(!$create)
		{
			$aux = $Model->find('first', array(
				'conditions' => array("{$Model->alias}.id" => $Model->data[$Model->alias]['id']),
				'recursive' => -1
				)
			);
				
			$this->snapshots[$Model->alias] = $aux[$Model->alias];
		}
	}
	
	/**
	 * Constrói e persiste informações relevantes sobre a transação através
	 * do Modelo Logger
	 * 
	 * @param Model $Model
	 * @param bool $create 
	 */
	protected function logQuery(&$Model, $create = true)
	{
		$diff = $Model->data[$Model->alias];
		
		if(!$create)
		{
			$diff = $this->diffRecords($this->snapshots[$Model->alias], $Model->data[$Model->alias]);
		}
		
		$msg = $this->buildHumanMessage($this->settings[$Model->alias]['format'], $create, $diff);
		
		$ds = $Model->getDataSource();
		$statement = '';
		
		if(method_exists($ds, 'getLog'))
		{
			$logs = $ds->getLog();
			$logs = array_pop($logs['log']);
			$statement = $logs['query'];
		}
		
		$toSave = array(
			'user_id' => $this->activeUserId,
			'model_alias' => $Model->alias,
			'model_id' => $Model->id,
			'description' => $msg,
			'statement' => $statement,
		);
		
		// Salva a entrada nos logs. Caso haja falha, usa o Log default do Cake para registrar a falha
		if($this->Logger->save($toSave) === false)
		{
			CakeLog::write(LOG_WARNING, sprintf(__d('auditable', "Can't save log entry for statement: '%s'"), $statement));
		}
	}
	
	/**
	 * Cria uma mensagem facilmente entendida por humanos
	 * 
	 * @param string $tmpl 
	 * @param bool $create 
	 * @param array $diff caso seja criação de registro ou um array, caso
	 * seja alteração
	 * 
	 * @return string Mensagem legível para humanos
	 */
	private function buildHumanMessage($tmpl, $create, $diff = array())
	{
		$placeHolders = array(
			'action' => $create ? __d('auditable', 'created') : __d('auditable', 'modified')
		);
		
		$humanDiff = '';
		$fieldLiteral = __d('auditable', 'field');
		
		if(!$create)
		{
			foreach($diff as $field => $changes)
			{
				$humanDiff .= $fieldLiteral . " $field ({$changes['old']} -> {$changes['new']})\n";
			}
		}
		else
		{
			foreach($diff as $field => $value)
			{
				$humanDiff .= $fieldLiteral . " $field ($value)\n";
			}
		}
		
		$placeHolders['data'] = $humanDiff;
		
		$msg = String::insert(__d('auditable', $tmpl), $placeHolders);
		
		return $msg;
	}
	
	/**
	 * Computa as alterações no registro e retorna um array formatado
	 * com os valores antigos e novos
	 * 
	 * 'campo' => array('old' => valor antigo, 'new' => valor novo)
	 * 
	 * @param array $old
	 * @param array $new
	 * 
	 * @return array $formatted
	 */
	private function diffRecords($old, $new)
	{
		$diff = Set::diff($old, $new);
		$formatted = array();
		
		foreach($diff as $key => $value)
		{
			$formatted[$key] = array('old' => $old[$key], 'new' => $new[$key]);
		}
		
		return $formatted;
	}
}