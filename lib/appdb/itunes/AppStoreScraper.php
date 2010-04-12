<?php
namespace appdb\itunes;

use \SimpleXmlElement;
use \appdb\itunes\exceptions\AppNotFoundException;
use \appdb\itunes\exceptions\InvalidITunesIDException;

class AppStoreScraper {
	protected $itunes_id, $xml;
	protected $iTunesURL_preID = "http://ax.phobos.apple.com.edgesuite.net/WebObjects/MZStore.woa/wa/viewSoftware?id=";
	protected $iTunesURL_postID = "&mt=8";
	protected $iTunesUserAgent = 'iTunes/8.2 (Macintosh; U; Intel Mac OS X 10_5_7)';
	
	public function __construct($itunes_id) {
		if ($itunes_id !== ((int)$itunes_id))
			throw new InvalidITunesIDException('Supplied iTunes ID was not an integer.');
		$this->itunes_id = (int)$itunes_id;
		$this->xml = $this->getAppStoreXML($this->itunes_id);
	}
	
	protected function getAppStoreXml($itunes_id) {
		$xmlurl = $this->iTunesURL_preID . $itunes_id . $this->iTunesURL_postID;
		$xml = $this->getXmlPage($xmlurl);
		if ($xml === false || !$xml->iTunes)
			throw new AppNotFoundException('Could not load iTunes page for provided iTunes ID.');
		return $xml;
	}
	
	protected function getXmlPage($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_REFERER, "");
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->iTunesUserAgent);
		curl_setopt($ch, CURLOPT_ENCODING, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$xml = curl_exec($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if ($errno == 28)
			throw new TimeoutException('Connection to iTunes server timed out.');
		else if ($errno)
			return false;
		try {
			$xml = str_replace("xmlns=", "a=", $xml);
			if (trim($xml) == '')
				return false;
			$xml = new SimpleXmlElement($xml, LIBXML_NOCDATA);
		}
		catch (Exception $e) {
			return false;
		}
		return $xml;
	}
	
	public function getITunesID() {
		return $this->itunes_id;
	}
	
	public function getName() {
		return trim($this->xml->iTunes);
	}
	
	public function getIconUrlJPG() {
		$result = $this->xml->xpath("//PictureView[contains(@url, '100x100-75')]/@url");
		if (!$result)
			return false;
		return trim($result[0]['url']);
	}
	
	public function getIconUrlPNG() {
		if (($icon = $this->getIconUrlJPG()) === false)
			return false;
		return substr($icon, 0, -3) . 'png';
	}
	
	public function getCompany() {
		$result = $this->xml->xpath("//GotoURL[contains(@url, '/artist/')]/@draggingName");
		if (!$result)
			return false;
		return trim($result[0]['draggingName']);
	}
	
	public function getVersion() {
		$vformat = "Version: ";
		$result = $this->xml->xpath("//TextView/SetFontStyle/text()[contains(., '$vformat')]");
		if (!$result)
			return false;
		$ver = trim(substr($result[0], strpos($result[0], ":") + 1));
		if (strlen($ver) < 1)
			return false;
		return $ver;
	}
	
	public function getCategory() {
		$format = "Category: ";
		$result = $this->xml->xpath("//TextView/SetFontStyle/text()[contains(., '$format')]");
		if (!$result)
			return false;
		$res = trim(preg_replace('/[\\n\\r]/', '', $result[0]));
		$check = substr($res, 0, strlen($format));
		if ($check != $format)
			return false;
		return substr($res, strlen($format), strlen($res));
	}
	
	public function getPrice() {
		$result = $this->xml->xpath("//HBoxView/VBoxView/TextView/SetFontStyle/b/text()[starts-with(., '$') or starts-with(., 'Free')]");
		if (!$result)
			return false;
		$res = trim(preg_replace("/[\\n\\r]/", '', $result[0]));
		return $res;
	}
	
	public function getReleaseDate() {
		$format = "Released";
		$result = $this->xml->xpath("//TextView/SetFontStyle/text()[contains(., '$format')]");
		if (!$result)
			return false;
		$res = trim($result[0]);
		$res = trim(preg_replace('/[\\r\\n\\t]/', '', substr($res, strlen($format), strlen($res))));
		return $res;
	}
	
	public function getScreenshots() {
		$result = $this->xml->xpath("//LoadFrameURL[@transitionModifier='left']/@url");
		if (!$result)
			return false;
		preg_match('/\?index=(\d+)/', $result[0]['url'], $matches);
		if (!isset($matches[1]) || !$matches[1])
			return false;
		$numShots = $matches[1] + 1;
		$shots = array();
		$img = $this->xml->xpath("//PictureView[@alt='Application Screenshot']");
		if ($img)
			$shots[] = trim($img[0]['url']);
		for ($i = 1; $i < $numShots; $i++) {
			$url = 'http://ax.itunes.apple.com/WebObjects/MZStore.woa/wa/screenshotWidget?index=' . 
				$i . '&id=' . $this->itunes_id;
			$xml = $this->getXmlPage($url);
			if ($xml === false)
				break;
			$img = $xml->xpath("//PictureView[@alt='Application Screenshot']");
			if ($img)
				$shots[] = trim($img[0]['url']);
		}
		return $shots;
	}
	
	public function getSeller() {
		$format = "Seller: ";
		$result = $this->xml->xpath("//TextView/SetFontStyle/text()[contains(., '$format')]");
		if (!$result)
			return false;
		$res = trim(preg_replace('/[\\n\\r]/', '', $result[0]));
		$check = substr($res, 0, strlen($format));
		if ($check != $format)
			return false;
		return substr($res, strlen($format), strlen($res));
	}
	
	public function getSize() {
		$format = " MB";
		$result = $this->xml->xpath("//TextView/SetFontStyle/text()[contains(., '$format')]");
		if (!$result)
			return false;
		$res = trim($result[0]);
		return $res;
	}
	
	public function getDescription() {
		return $this->getTextBlock('APPLICATION DESCRIPTION');
	}
	
	public function getVersionInfo() {
		return $this->getTextBlock("S NEW IN THIS VERSION:");
	}
	
	public function getLanguages() {
		return $this->getTextBlock('LANGUAGES');
	}
	
	public function getRequirements() {
		return $this->getTextBlock('REQUIREMENTS');
	}
	
	protected function getTextBlock($seek) {
		$regexDestroy = array(
			'\\<SetFontStyle\\snormalStyle\\=\\"descriptionTextColor\\"\\>',
			'\\<\\/SetFontStyle\\>'
		);
		$result = $this->xml->xpath("//TextView/SetFontStyle/b[contains(text(), '$seek')]/parent::SetFontStyle/parent::TextView/following-sibling::TextView[1]/SetFontStyle");
		if (!$result)
			return false;
		$res = $result[0]->asXML();
		foreach ($regexDestroy as $kill)
			$res = preg_replace('/' . $kill . '/', '', $res);
		return trim($res);
	}
	
}

?>