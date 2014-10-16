<?php

class BaseConfig {

/**
 * [$environments description]
 *
 * @var array
 */
	public $environments = array('default', 'cloud', 'produccion');

/**
 * [$default description]
 *
 * @var array
 */
	public $default = array();

/**
 * [__construct description]
 */
	public function __construct() {
		$environment = $this->_getEnvironmentName();
		if ($environment && isset($this->{$environment})) {
			$this->default = array_merge($this->default, $this->{$environment});

			$this->auditoria = $this->default;
			$this->auditoria['database'] = Configure::read('auditable.name');
		}
		$this->test = $this->default;
	}

/**
 * getEnvironmentName
 *
 * @return string
 */
	protected function _getEnvironmentName() {
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
