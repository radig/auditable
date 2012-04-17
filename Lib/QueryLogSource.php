<?php
/**
 * Lib que recupera e permite fazer cache das queries SQL geradas pelo CakePHP.
 *
 * Funciona em conjunto com o behavior Auditable, retornando para ele as queries
 * de um determinado modelo.
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
 * @subpackage Auditable.Lib
 */

App::uses('Set', 'Utility');

class QueryLogSource {

	/**
	 * Armazena as queries cacheadas.
	 *
	 * @var array
	 */
	private $cachedQueries = array();

	private $mapActionSql = array(
		'create' => 'INSERT',
		'modify' => 'UPDATE',
		'delete' => 'DELETE',
	);

	/**
	 * Inicializar a propriedade DataSource
	 * e habilita o debug de queries.
	 *
	 * @param Model $Model
	 */
	public function __construct(&$Model = null)
	{
		if($Model !== null)
		{
			// Habilita Log das queries
			$this->enable($Model);
		}
	}

	/**
	 * Desabilita o log de query no CakePHP
	 *
	 * @return void
	 */
	public function disable(&$Model)
	{
		$Model->getDataSource()->fullDebug = false;
	}

	/**
	 * Habilita o log de query no CakePHP
	 *
	 * @return void
	 */
	public function enable(&$Model)
	{
		$Model->getDataSource()->fullDebug = true;
	}

	/**
	 * Recupera todas as queries que foram registradas
	 * pelo datasource para um determinado modelo.
	 *
	 * @param Model $Model Instancia do modelo que terá
	 * as queries retornadas.
	 *
	 * @param  string $action 'create' | 'modify' | 'delete'
	 *
	 * @param bool $associateds Habilita ou não o retorno
	 * de queries geradas por modelos relacionados. Não
	 * implementado ainda.
	 *
	 * @return array
	 */
	public function getModelQueries($Model, $action = 'create', $associateds = false)
	{
		$queries = $this->getCleanLog($Model);
		$valids = array(); // queries do modelo Model
		$table = $Model->tablePrefix . $Model->table; // monta nome completo da tabela

		foreach ($queries as $query)
		{
			// Guarda apenas queries do modelo atual que não seja SELECT
			if(strpos($query, $table) !== false && strpos($query, $this->mapActionSql[$action]) !== false)
			{
				$valids[] = $query;
			}
		}

		return $valids;
	}

	/**
	 * Método auxiliar para recuperar a lista de queries do DataSource
	 * e remover campos indesejados.
	 *
	 * @return array
	 */
	protected function getCleanLog($Model)
	{
		$ds = $Model->getDataSource();
		$queries = array();

		if(method_exists($ds, 'getLog'))
		{
			$log = $ds->getLog(false, false);
			$diff = Set::diff($log['log'], $this->cachedQueries);

			$this->cachedQueries = $log['log'];

			foreach($diff as $entry)
				$queries[] = $entry['query'];
		}

		return $queries;
	}
}