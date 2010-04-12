<?php
namespace appdb\api;

interface RequestDecoder {
	public function decodeRequest($data, $profile, &$errorcode);
}

?>