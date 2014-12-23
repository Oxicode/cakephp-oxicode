<?php

class RestController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'Session', 'RequestHandler');

	protected function _apiFallo($mensaje = 'Bad request') {
		$this->response->type('json');
		$this->response->statusCode(400);

		$mensaje = [
			'status' => 'ERROR',
			'message' => $mensaje
		];
		$this->response->body(json_encode($mensaje));
		$this->response->send();
		$this->_stop();
	}

/**
 * REST api dispatcher
 */
	public function disparador() {
		# Load the appropriate version of the api
		$api['version'] = $this->params['version'];

		# Detect method: get/post/put/delete
		$api['method'] = strtolower($_SERVER['REQUEST_METHOD']);

		# Override the method when it is explicitly set
		if (isset($this->params->query['method'])) {
			$api['method'] = strtolower($this->params->query['method']);
			unset($this->params->query['method']);
		}

		# Define the noun
		$api['modelo'] = ucwords(Inflector::singularize($this->params['noun']));
		$api['controller'] = Inflector::pluralize($this->params['noun']);

		$this->loadModel($api['modelo']);

		# Check if we have a passed argument we should use
		if (isset($this->request->params['pass'][1])) :
			$api['id'] = $this->request->params['pass'][1];

			if ($api['id'] === 0)
				return $this->_apiFallo('ID inválido');
		endif;

		# Define possible parameters
		$api['parameters'] = $this->request->query;

		# If the header has signature and key, override the api['parameters']-value
		#if (isset($header['HTTP_KEY']))
		#	$api['parameters']['key'] = $header['HTTP_KEY'];

		if (isset($header['HTTP_SIGNATURE']))
			$api['parameters']['signature'] = $header['HTTP_SIGNATURE'];

		# Check if we need to suppress the response codes
		if (isset($api['parameters']['suppress_response_code'])) {
			unset($api['parameters']['suppress_response_code']);
			$api['suppress_response_code'] = true;
		}

		# Check if we are debugging: ?debug should be set (or debug should be defined in header)
		if (isset($api['parameters']['debug']) || isset($header['HTTP_DEBUG'])) {
			unset($api['parameters']['debug']);
			$api['debug'] = true;

			$result['call'] = $api;
		}

		if (empty($this->request->params['pass'][0]))
			return $this->_apiFallo('Metodo no encontrado');

		$action = 'api_' . $this->request->params['pass'][0];
		if (! method_exists($this, $action))
			return $this->_apiFallo('Metodo no encontrado');

		if (empty($api['parameters']['key'])) {
			$api['key'] = 'id';
		} else {
			$api['key'] = $api['parameters']['key'];
			unset($api['parameters']['key']);
			if (!ClassRegistry::init($api['controller'])->hasField($api['key']))
				return $this->_apiFallo('Key no encontrado');
		}
		$this->setAction($action, $api);
	}

	public function api_view($api = array()) {
		$this->loadModel($api['modelo']);

		if (empty($api['parameters']['nivel']))
			$api['parameters']['nivel'] = 1;

		$api['parameters']['nivel'] = (int) $api['parameters']['nivel'];

		if ($api['parameters']['nivel'] > 4 || $api['parameters']['nivel'] < 1)
			$api['parameters']['nivel'] = 1;

		$api['parameters']['recursive'] = $api['parameters']['nivel'] - 2;

		$result = $this->{$api['modelo']}->find('first', array(
			'conditions' => array($api['modelo'] . "." . $api['key'] => $api['id']),
			'recursive' => $api['parameters']['recursive']
		));

		unset($api['parameters']['recursive']);
		$this->set(compact('api', 'result'));
		$this->set('_serialize', ['api', 'result']);
	}

	public function login($api = array()) {

		if ($this->request->is(array('post', 'put'))) {

			if (empty($this->request->data['username']) || empty($this->request->data['password'])) :
				$result = array('mensaje' => 'fallo');
			else :
			$datos['User']['username'] = $this->request->data['username'];
			$datos['User']['password'] = $this->request->data['password'];

			$existe = ClassRegistry::init('User')->existe($datos['User']);

			if ($existe !== true) :
					$result = array('mensaje' => 'fallo');
			else :
				$usuario = ClassRegistry::init('User')->findByUsername($datos['User']['username']);

				if ($this->Auth->login($usuario['User'])) :
					$result = array('mensaje' => 'xvr');
				else :
					$result = array('mensaje' => 'fallo');
				endif;
			endif;
			endif;

			$this->set(compact('api', 'result'));
			$this->set('_serialize', ['api', 'result']);
		}

	}

	public function api_index($api = array()) {
		$this->loadModel($api['modelo']);

		$options = [
			'limit' => 20,
			'recursive' => -1
		];

		foreach ($api['parameters'] as $key => $value) :
			if ($key === 'limit' && is_numeric($value)) :
				$options[$key] = $value;
			endif;

			if ($key === 'recursive' && is_numeric($value) && $value < 3) :
				$options[$key] = $value;
			endif;
		endforeach;

		foreach ($this->{$api['modelo']}->hasMany as $key => &$value) :
			$value['limit'] = $options['limit'];
		endforeach;

		if ( !empty($api['parameters']['recursive']) && $api['parameters']['recursive'] < 2)
			$options['recursive'] = $api['parameters']['recursive'];

		$api['parameters']['limit'] = $options['limit'];
		$this->Paginator->settings = array(
			'maxLimit' => 200,
			'paramType' => 'querystring',
			'recursive' => $options['recursive'],
			'limit' => $options['limit']
		);
		$result = $this->Paginator->paginate($api['modelo']);

		$this->set(compact('api', 'result'));
		$this->set('_serialize', ['api', 'result']);
	}

}
