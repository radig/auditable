<?php
class AuditableConfig
{
	/**
	 * Referência estática para os
	 * dados do usuário ativo
	 * 
	 * @var array
	 */
	static public $userId = null;
	
	/**
	 * Referência estática para os dados
	 * do modelo de log
	 * 
	 * @var Model
	 */
	static public $Logger = null;
	
	/**
	 * Nome da função utilizada na serialização
	 * dos dados antes de persistir o log.
	 * 
	 * @var string
	 */
	static public $serialize = 'serialize';
	
	/**
	 * Nome da função utilizada na deserialização
	 * dos dados ao recupera-los dos logs.
	 * 
	 * @var string
	 */
	static public $unserialize = 'unserialize';
}