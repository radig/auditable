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
}