<?php
App::uses('HttpSocket', 'Network/Http');
/**
 * Mailgun class
 *
 * Enables sending of email over mailgun
 *
 * Licensed under The MIT License
 *
 * @author Brad Koch <bradkoch2007@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BasicTransport extends AbstractTransport {

/**
 * Configurations
 *
 * @var array
 */
	protected $_config = array();

/**
 * Send
 *
 * @param CakeEmail $email objeto mail
 * @return array
 * @throws SocketException
 */
	public function send(CakeEmail $email) {
		$http = new HttpSocket();

		$url = 'https://api.mailgun.net/v2/' . $this->_config['mailgun_domain'] . '/messages';
		$post = array();
		$postPreprocess = array_merge(
			$email->getHeaders(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc', 'subject')),
			array(
				'text' => $email->message(CakeEmail::MESSAGE_TEXT),
				'html' => $email->message(CakeEmail::MESSAGE_HTML)
			)
		);
		foreach ($postPreprocess as $k => $v) {
			if (! empty($v)) {
				$post[strtolower($k)] = $v;
			}
		}
		$request = array(
			'auth' => array(
				'method' => 'Basic',
				'user' => 'api',
				'pass' => $this->_config['api_key']
			)
		);

		$response = $http->post($url, $post, $request);
		if ($response === false) {
			throw new SocketException("Mailgun BasicTransport error, no response", 500);
		}

		$httpStatus = $response->code;
		if ($httpStatus != 200) {
			throw new SocketException("Mailgun request failed.  Status: $httpStatus, Response: {$response->body}", 500);
		}

		return array(
			'headers' => $this->_headersToString($email->getHeaders(), PHP_EOL),
			'message' => implode(PHP_EOL, $email->message())
		);
	}

}
