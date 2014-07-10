cakephp-modo-mantenimiento
==========================
bootstrap.php
[code]
Configure::write('MaintenanceMode', array(
	'enabled' => true,
	'view' =>   array(
		'layout' => 'error',
		'template' => 'Mantenimiento/index'
	),
	'ip_filters' => array('227.0.*.*')
));
Configure::write('Dispatcher.filters', array(
	'AssetDispatcher',
	'CacheDispatcher',
	'ModoMantenimiento.MaintenanceMode' ## this line
));
[/code]
