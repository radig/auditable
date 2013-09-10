<?php
App::uses('AppModel', 'Model');
/**
 * Modelo de Exemplo para persistir os detalhes dos logs
 *
 * PHP version > 5.3.1
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Radig - Soluções em TI, www.radig.com.br
 * @link http://www.radig.com.br
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @package radig.Auditable
 * @subpackage Model
 */
class LogDetail extends AppModel
{
	public $name = 'LogDetail';

	public $useTable = 'log_details';

	public $hasOne = array(
		'Logger' => array('className' => 'Auditable.Logger')
	);
}
