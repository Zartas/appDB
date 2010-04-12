<?php
namespace appdb\api\encoders;

use appdb\api\ResponseEncoder;
use appdb\api\ResponseCode;

class PlainTextEncoder implements ResponseEncoder {
	
	public function encodeResponse($data, &$errorcode) {
		return $data;
	}
}

?>