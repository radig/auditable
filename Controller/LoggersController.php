<?php
/**
 * 
 * 
 * @property Logger $Logger 
 */
class LoggersController extends AppController
{
	public $helpers = array('Auditable.Auditor');
	
	public function index()
	{
		$this->set('loggers', $this->paginate());
	}
	
	public function view($id)
	{
		$this->Logger->id = $id;
		
		if(!$this->Logger->exists())
		{
			throw new NotFoundException(__d('auditable', 'Log entry could not be find.'));
		}
		
		$this->set('logger', $this->Logger->get($id));
	}
}