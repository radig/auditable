<?php
class LoggersController extends AppController
{
	public $helpers = array('Auditable.Auditor');
	
	public function index()
	{
		$this->set('loggers', $this->paginate());
	}
}