<?php
/**
 * Controller de exemplo para consultar os logs registrados, juntamente
 * com o Helper Auditor.
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
 * @subpackage Controller
 */
class LoggersController extends AppController
{
	public $helpers = array('Auditable.Auditor');
	
	public function index() {
		$this->set('loggers', $this->paginate());
	}
	
	public function view($id) {
		$this->Logger->id = $id;
		
		if(!$this->Logger->exists()) {
			throw new NotFoundException(__d('auditable', 'Log entry could not be find.'));
		}
		
		$this->set('logger', $this->Logger->get($id));
	}
}
