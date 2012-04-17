<?php
/**
 * Helper para auxiliar na exibição dos logs gravados pelo behavior Auditable.
 *
 * Permite a formatação de acordo com as preferências do usuário das alterações
 * efetuadas em cada entrada do log.
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
 * @subpackage Auditable.View.Helper
 */

App::uses('AuditableConfig', 'Auditable.Lib');
App::uses('String', 'Utility');
class AuditorHelper extends AppHelper
{
	/**
	 * Configurações padrões
	 *
	 * @var array
	 */
	public $settings = array(
		'formats' => array(
			'general' => "Record :action:<br />\n :data",
			'prepend' => "Field",
			'pospend' => "<br />\n",
			'create' => "\":field\" as \":value\"",
			'modify' => "\":field\" from \":old\" to \":new\"",
			'delete' => "\":field\" as \":value\""
		)
	);

	/**
	 * ENUM auxiliar para os tipos de logs
	 *
	 * @var array
	 */
	protected $typesEnum = array(
		'undefined',
		'create',
		'modify',
		'delete'
	);

	/**
	 * Retorna a string localizada que representa
	 * o tipo numérico do log.
	 *
	 * @param int $t
	 * @return string
	 */
	public function type($t)
	{
		return ucfirst(__d('auditable', $this->typesEnum[$t]));
	}

	/**
	 * Formata uma entrada de log gerada pelo AuditableBehavior para fácil
	 * visualização na view, baseada nas configurações do helper.
	 *
	 * @param array $data
	 * @param int $type
	 *
	 * @return string
	 */
	public function format($data, $type)
	{
		$func = 'unserialize';

		if(is_callable(AuditableConfig::$unserialize))
		{
			$func = AuditableConfig::$unserialize;
		}

		$data = call_user_func($func, $data);

		$placeHolders = array();
		$prepend = __d('auditable', $this->settings['formats']['prepend']) . ' ';
		$pospend = ' ' . __d('auditable', $this->settings['formats']['pospend']);
		$humanDiff = '';
		$action = $this->typesEnum[$type];
		$actionMsg = __d('auditable', $this->settings['formats'][$action]);

		switch($type)
		{
			case 2:
				$placeHolders['action'] = __d('auditable', 'modified');

				foreach($data as $field => $changes)
					$humanDiff .= $prepend . String::insert($actionMsg, array('field' => $field, 'old' => $changes['old'], 'new' => $changes['new'])) . $pospend;

				break;

			case 1:
				$placeHolders['action'] = __d('auditable', 'created');

			case 3:
				if(!isset($placeHolders['action']))
					$placeHolders['action'] = __d('auditable', 'deleted');

				foreach($data as $field => $value)
					$humanDiff .= $prepend . String::insert($actionMsg, compact('field', 'value')) . $pospend;

				break;

			default:
				$placeHolders['action'] = __d('auditable', 'undefined');
				$humanDiff .= __d('auditable', 'nothing changed');
				break;
		}

		$placeHolders['data'] = $humanDiff;

		$msg = String::insert(__d('auditable', $this->settings['formats']['general']), $placeHolders);

		return $msg;
	}
}