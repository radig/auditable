<?php
class Logger extends AppModel
{
	public $useTable = 'logs';
	
	public $belongsTo = array('LogDetail');
}