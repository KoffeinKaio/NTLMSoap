<?php

namespace NTLMSoap;
use Exception;
require_once("HttpStream/NTLMStream.php");

class Client extends \SoapClient {

	private $options = Array();
	private $__last_request;

	/**
	 *
	 * @param String $url The WSDL url
	 * @param Array $data Soap options, it should contain ntlm_username and ntlm_password fields
	 * @throws Exception
	 * @see \SoapClient::__construct()
	 */
	public function __construct($url, $data){

		$this->options = $data;

		if(empty($data['ntlm_username']) && empty($data['ntlm_password'])){
			parent::__construct($url, $data);
		}else{
			$this->use_ntlm	= true;
			HttpStream\NTLMStream::$user = $data['ntlm_username'];
			HttpStream\NTLMStream::$password = $data['ntlm_password'];

			stream_wrapper_unregister('http');
			if(!stream_wrapper_register('http', '\\NTLMSoap\\HttpStream\\NTLMStream')){
				throw new Exception("Unable to register HTTP Handler");
			}

			$time_start = microtime(true);
			parent::__construct($url, $data);

			stream_wrapper_restore('http');
		}

	}

	/**
	 * (non-PHPdoc)
	 * @see SoapClient::__doRequest()
	 */
	public function __doRequest($request, $location, $action, $version, $one_way=0) {
		$this->__last_request = $request;
		$start_time	= microtime(true);

		$ch = curl_init($location);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Method: POST',
			'User-Agent: PHP-SOAP-CURL',
			'Content-Type: text/xml; charset=utf-8',
			'SOAPAction: "' . $action . '"',
		));

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		if(!empty($this->options['ntlm_username']) && !empty($this->options['ntlm_password'])){
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->options['ntlm_username'].':'. $this->options['ntlm_password']);
		}

		$response = curl_exec($ch);
		return $response;
	}

}
