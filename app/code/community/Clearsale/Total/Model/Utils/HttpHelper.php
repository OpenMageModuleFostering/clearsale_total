<?php

class Clearsale_Total_Model_Utils_HttpHelper
{

public function PostData($data, $url) {
		
		$return = new Clearsale_Total_Model_Utils_HttpMessage();
		
		$dataString =  $this->json_encode_unicode($data);
		
		 $isLogenabled = Mage::getStoreConfig("clearsale_total/general/enabled_log");
		
		if($isLogenabled)
		{
		 $csLog = Mage::getSingleton('total/log');
		 $csLog->log($dataString);
		}
				
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($dataString))
		);

		$return->Body = curl_exec($ch);
		
		if(!$return) {
			die(curl_error($ch));
		}else
		{
			$return->HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		}
		
		curl_close($ch);
		
		$jsonReturn = $return->Body;
		
		if($isLogenabled)
		{
		 $csLog = Mage::getSingleton('total/log');
		 $csLog->log($jsonReturn);
		}
		
		if($return->HttpCode != 200)
		{
			$csLog = Mage::getSingleton('total/log');			
			$csLog->log($return->Body);
		}
		
		return $return;
	}

public function json_encode_unicode($data) {
	if (defined('JSON_UNESCAPED_UNICODE')) {
		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}
	return preg_replace_callback('/(?<!\\\\)\\\\u([0-9a-f]{4})/i',
		function ($m) {
			$d = pack("H*", $m[1]);
			$r = mb_convert_encoding($d, "UTF8", "UTF-16BE");
			return $r!=="?" && $r!=="" ? $r : $m[0];
		}, json_encode($data)
	);
}

}