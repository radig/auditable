<?php
Router::connect('/auditable', array('plugin' => 'auditable', 'controller' => 'loggers'));
Router::connect('/auditable/view/*', array('plugin' => 'auditable', 'controller' => 'loggers', 'action' => 'view'));