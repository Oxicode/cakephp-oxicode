Cakephp-modo-mantenimiento

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
---

CakePHP Mailgun Plugin
This package provides two Mailgun transports - one implemented using CakePHP's HttpRequest utility and the other using curl.

Installation

If you haven't already, sign up for a Mailgun account.

Install this package as a CakePHP Plugin.

```
CakePlugin::load('Mailgun');
```

Usage

To enable the transport, add the following information to your Config/email.php:

```
class EmailConfig {
    public $mailgun = array(
        'transport' => 'Mailgun.Basic',
        'mailgun_domain'    => 'my-mailgun-domain.com',
        'api_key'   => 'MY_MAILGUN_API_KEY'
    );
}
```

Use the CakeEmail class as normal, invoking the new configuration settings.

```
$email = new CakeEmail('mailgun');
```
==========================

Gracias a
* https://github.com/awebdeveloper/cakephp-maintenance-mode
* http://josediazgonzalez.com/2013/12/13/simple-application-maintenance-mode/
* https://github.com/faranshery/cakephp-mailgun
