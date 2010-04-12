<?php
namespace appdb\api\decoders;

use appdb\api\RequestDecoder;
use appdb\api\ResponseCode;

class PlainTextDecoder implements RequestDecoder {
	
	public function decodeRequest($data, $profile, &$errorcode) {
		$result = json_decode($data->request);
		if ($result === false) {
			$errorcode = ResponseCode::BAD_REQUEST;
			return false;
		}
		return $result;
	}
}

?>