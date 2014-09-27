<?php

class BaseConfig {

	public $environments = array('default', 'cloud', 'produccion');

	public $default = array();

	function __construct() {
		$environment = $this->getEnvironmentName();
		if ($environment && isset($this->{$environment})) {
			$this->default = array_merge($this->default, $this->{$environment});

			$this->auditoria = $this->default;
			$this->auditoria['database'] = Configure::read('auditable.name');
		}
		$this->test = $this->default;
	}

	function getEnvironmentName() {
		$environment = "default";
		if (php_sapi_name() !== 'cli') {
			$server = env('HTTP_HOST');
			foreach ($this->environments as $e) {
				if (isset($this->{$e}) && isset($this->{$e}['environment']) && $this->{$e}['environment'] == $server) {
					$environment = $e;
					break;
				}
			}
		}
		return $environment;
	}
}
