<?php
/**
 * Md5Shell shell
 *
 * PHP 5
 *
 * Copyright 2010-2014, Oxicode
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     2010-2012 Marc Ypes, The Netherlands
 * @author        Ceeram
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');
App::uses('HttpSocket', 'Network/Http');

/**
 * Helps clear content of CACHE subfolders as well as content in cache engines from console
 *
 */
class Md5Shell extends AppShell {

	private $_url = 'http://www.md5online.org/';
	private $ignoreMissing = false;

	public function crypt() {
		while (empty($password)) {
			$password = $this->in('Ingrese la contraseña a encriptar');
		}

		return $this->out(md5($password));
	}

	public function decrypt() {
		while (empty($md5)) {
			$md5 = $this->in('Ingrese el Hash a buscar');
		}

		$HttpSocket = new HttpSocket();

		$md5 = trim($md5);
		if (strlen($md5) !== 32) {
			return $this->out('Hash invalido');
		}
		$matches = array();

		$html = $HttpSocket->get($this->_url);
		$pattern = '/<input type="hidden" name="a" value="(.*)">/Uis';
		preg_match_all($pattern, $html->body, $matches, PREG_SET_ORDER);

		if (empty($matches[0][1])) {
			return $this->out('El servidor no responde');
		}

		$a = $matches[0][1];
		$rest = $this->postDataViaCurl($HttpSocket, $md5, $a);
		do {
		    $this->out('..');
			sleep(1);
		}
		while ($rest->code === null);

		$returnArray = array();
		$pattern2 = '/Found (.*) <b>(.*)<\/b><\/span>/Uis';
		preg_match_all($pattern2, $rest, $returnArray, PREG_SET_ORDER);

		if (empty($returnArray[0][2])) {
			return $this->out('El servidor no responde');
		}
		$nt = trim(strip_tags($returnArray[0][2]));
		if (empty($nt)) {
			return false;
		}
		$this->out('Contraseña desencriptada: <info>' . $nt . '</info>');
	}

	private function postDataViaCurl($socket, $md5, $a) {
		$data = [
			'md5' => $md5,
			'search' => '0',
			'action' => 'decrypt',
			'Decrypt',
			'a' => $a
		];
		$results = $socket->post($this->_url, $data, ['timeout' => 10]);


		return $results->body;
	}

}
