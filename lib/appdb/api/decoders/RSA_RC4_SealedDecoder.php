<?php
namespace appdb\api\decoders;

use appdb\api\RequestDecoder;
use appdb\api\ResponseCode;

class RSA_RC4_SealedDecoder implements RequestDecoder {
	
	public function decodeRequest($data, $profile, &$errorcode) {
		$privkey = openssl_pkey_get_private($profile->priv_pem);
		$check = openssl_open(base64_decode($data->request), $decdata, base64_decode($data->enc_key), $privkey);
		if ($check < 1) {
			$errorcode = ResponseCode::FORBIDDEN;
			return false;
		}
		$result = json_decode($decdata);
		if ($result === false) {
			$errorcode = ResponseCode::BAD_REQUEST;
			return false;
		}
		return $result;
	}
}

?>