<?php
namespace appdb\api\encoders;

use appdb\api\ResponseEncoder;
use appdb\api\ResponseCode;
use appdb\api\KeyFactory;

class RSA_SHA1_SignedEncoder implements ResponseEncoder {
	
	public function encodeResponse($data, &$errorcode) {
		$data['data'] = json_encode($data['data']);
		$result = openssl_sign($data['data'], $sign, KeyFactory::getOpenSSLKey());
		if (!$result) {
			$errorcode = ResponseCode::INTERNAL_SERVER_ERROR;
			return false;
		}
		$data['signature'] = base64_encode($sign);
		return $data;
	}
}

?>