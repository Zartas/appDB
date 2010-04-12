<?php
namespace appdb\api;

interface ResponseEncoder {
	public function encodeResponse($data, &$errorcode);
}

?>