<?php
namespace appdb\api;

use appdb\api\ResponseCode;
use appdb\models\APIProfileModel;
use hydrogen\recache\RECacheManager;

class RequestProcessor {
	protected $cm;
	
	function __construct() {
		$this->cm = RECacheManager::getInstance();
	}
	
	public function processRequest($req) {
		if (!isset($req))
			return $this->createResponse(ResponseCode::BAD_REQUEST);
		$req = json_decode($req);
		if (is_null($req) || $req === false || !isset($req->profile) ||
				!isset($req->format) || !isset($req->request))
			return $this->createResponse(ResponseCode::BAD_REQUEST);
		if (isset($req->id) && strlen($req->id) < 20)
			$id = $req->id;
		else
			$id = false;
		$profile = $this->getProfile($req->profile);
		if ($profile === false || !$profile->active)
			return $this->createResponse(ResponseCode::FORBIDDEN, $id);
		$rawreq = $this->decodeData($req, $profile, $errorcode);
		if ($rawreq === false)
			return $this->createResponse($errorcode, $id);
		$rawresp = $this->processRawRequest($rawreq, $returncode, $profile, $req->format);
		if ($rawresp === false || $rawresp === true)
			return $this->createResponse($returncode, $id);
		$res = $this->createResponse($returncode, $id, $rawresp, 'RSA_SHA1_Signed');
		return $res;
	}
	
	public function handleErrors() {
		set_error_handler(array($this, 'handleAPIError'));
		set_exception_handler(array($this, 'handleAPIException'));
	}
	
	public function handleAPIError($errno, $errstr) {
		die($this->createResponse(ResponseCode::INTERNAL_SERVER_ERROR));
	}
	
	public function handleAPIException($exception) {
		$this->handleAPIError($exception->getCode(), $exception->getMessage());
	}
	
	protected function createResponse($code, $id=false, $data=false, $format='PlainText') {
		$response = array(
			"code" => $code
			);
		if ($id !== false)
			$response['id'] = $id;
		if ($data !== false) {
			$response['format'] = $format;
			$response['data'] = $data;
			$encoder = $this->getEncoder($format);
			if (!$encoder)
				return $this->createResponse(ResponseCode::INTERNAL_SERVER_ERROR, $id);
			$response = $encoder->encodeResponse($response, $errorcode);
			if (!$response)
				return $this->createResponse($errorcode, $id);
		}
		return json_encode($response);
	}
	
	protected function getProfile($name) {
		$apm = APIProfileModel::getInstance();
		return $apm->getByNameCached($name);
	}
	
	protected function decodeData($fullRequest, $profile, &$errorcode) {
		$decoder = $this->getDecoder($fullRequest->format);
		if (!$decoder) {
			$errorcode = ResponseCode::FORMAT_NOT_ACCEPTED;
			return false;
		}
		return $decoder->decodeRequest($fullRequest, $profile, $errorcode);
	}
	
	protected function getDecoder($name) {
		$decoderClass = '\appdb\api\decoders\\' . $name . 'Decoder';
		if (\appdb\classProbe($decoderClass))
			return new $decoderClass();
		return false;
	}
	
	protected function getEncoder($name) {
		$encoderClass = '\appdb\api\encoders\\' . $name . 'Encoder';
		if (\appdb\classProbe($encoderClass))
			return new $encoderClass();
		return false;
	}
	
	public function processRawRequest($rawreq, &$returncode, &$profile, $format=false) {
		if (!isset($rawreq->object) || !isset($rawreq->action)) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		$objClass = '\appdb\api\objects\\' . $rawreq->object . 'Object';
		if (\appdb\classProbe($objClass))
			return $objClass::callAction($rawreq->action, $rawreq, $returncode, $profile, $format);
		$returncode = ResponseCode::OBJECT_NOT_FOUND;
		return false;
	}
}

?>