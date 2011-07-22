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
 * Ainda utiliza uma tabela auxiliar 'logs' (ou outra configurada para isso) para registrar
 * ações de inserção/edição e manter um histórico contínuo de alterações.
 * 
 * PHP version > 5.3.1
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @copyright 2011, Radig - Soluções em TI, www.radig.com.br
 * @link http://www.radig.com.br
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @package radig
 * @subpackage auditable.models.behaviors
 */
class AuditableBehavior extends ModelBehavior
{
	public $settings = array();
	
	public $defaults = array(
		'userModel' => 'User',
		'staticNotation' => false,
		'userKey' => 'user_id',
		'format' => '',
		'skip' => array(),
		'fields' => array(
			'create' => 'created_by',
			'modify' => 'modified_by'
		)
	);
	
	/**
	 * Dados do usuário ativo, formatado
	 * como um resultado de um find('first)
	 * 
	 * @var array
	 */
	protected $_User = null;
	
	/**
	 * Chamado pelo CakePHP para iniciar o behavior
	 *
	 * @param Model $Model
	 * @param array $config
	 * 
	 * Opções de configurações:
	 *   userModel      : 'User'. Nome da classe de usuários usado em sua aplicação.
	 *   staticNotation : false. Usa acesso estático a class userModel para acessar os dados do usuário logado
	 *   userKey        : 'user_id'. Nome do campo que será salvo no log como quem executou a ação.
	 *   skip           : array(). Lista com nome das ações que devem ser ignoradas pelo log.
	 */
	public function setup(&$Model, $config = array())
	{
		if (!is_array($config))
		{
			$config = array();
		}
		
		$this->settings[$Model->alias] = array_merge($this->defaults, $config);
		
		if($this->settings[$Model->alias]['staticNotation'])
		{
			App::import('Model', $this->settings[$Model->alias]['userModel']);
			
			$userModel = $this->settings[$Model->alias]['userModel'];
			
			$this->_User = $userModel::get();
		}
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
		$this->_User = $userData;
		
		return true;
	}
	
	public function beforeSave(&$Model)
	{
		// Caso seja a criação de um registro
		if(empty($Model->data[$Model->alias]['id']))
		{
			$Model->data[$Model->alias][$this->settings[$Model->alias]['fields']['create']] = $this->_User['User']['id'];
		}
		// Caso contrário
		else
		{
			$Model->data[$Model->alias][$this->settings[$Model->alias]['fields']['modify']] = $this->_User['User']['id'];
		}
		
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
}