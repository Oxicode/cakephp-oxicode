<?php
	Router::connect('/api/:version/login',
		array('plugin' => 'Oxicode', 'controller' => 'Rest', 'action' => 'login', 'ext' => 'json'),
		array('version' => '1')
	);
	Router::connect('/api/:version/:noun/*',
		array('plugin' => 'Oxicode', 'controller' => 'Rest', 'action' => 'disparador', 'ext' => 'json'),
		array('version' => '[1-2]')
	);

	Router::mapResources('Oxicode.Rest', array('prefix' => 'api'));
	Router::resourceMap(array(
		array('action' => 'login', 'method' => 'POST', 'id' => false),
	));
