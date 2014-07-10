cakephp-modo-mantenimiento
==========================
bootstrap.php
```
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
```


Gracias a
* https://github.com/awebdeveloper/cakephp-maintenance-mode
* http://josediazgonzalez.com/2013/12/13/simple-application-maintenance-mode/
