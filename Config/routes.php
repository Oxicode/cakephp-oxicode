<?php
	Router::connect('/api/:version/:noun/*',
		array('plugin' => 'Oxicode', 'controller' => 'Rest', 'action' => 'disparador', 'ext' => 'json'),
		array('version' => '[1-2]')
	);

	Router::mapResources('Oxicode.Rest', array('prefix' => 'api'));

