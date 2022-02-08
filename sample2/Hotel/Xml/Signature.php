<?php

namespace Hotel\Xml;

class Signature {

	/**
	 * Get script name from URL (for use as parameter in $this->make, $this->check, etc.)
	 *
	 * @param string $url
	 * @return string
	 */
	public function getScriptNameFromUrl ( string $url ): string
	{
		$path = parse_url($url, PHP_URL_PATH);
		$len  = strlen($path);
		if ( $len === 0  ||  '/' == $path[$len-1]) {
			return "";
		}
		return basename($path);
	}
	
	/**
	 * Get name of currently executed script (need to check signature of incoming message using $this->check)
	 *
	 * @return string
	 */
	public function getOurScriptName(): string
	{
		return $this->getScriptNameFromUrl( $_SERVER["REQUEST_URI"] ?? $_SERVER['PHP_SELF'] );
	}

	/**
	 * Creates a signature
	 *
     * @param string $strScriptName script name
	 * @param array $arrParams  associative array of parameters for the signature
	 * @param string $strSecretKey
	 * @return string
	 */
	public function make (string $strScriptName, array $arrParams, string $strSecretKey): string
	{
		return md5( $this->makeSigStr($strScriptName, $arrParams, $strSecretKey) );
	}

    /**
     * Verifies the signature
     *
     * @param string $signature
     * @param string $strScriptName
     * @param array $arrParams associative array of parameters for the signature
     * @param string $strSecretKey
     * @return bool
     */
	public function check ( string $signature, string $strScriptName, array $arrParams, string $strSecretKey ): bool
	{
		return (string)$signature === $this->make($strScriptName, $arrParams, $strSecretKey);
	}


	/**
	 * Returns a string, a hash of which coincide with the result of the make() method.
	 * WARNING: This method can be used only for debugging purposes!
	 *
     * @param string $strScriptName script name
	 * @param array $arrParams  associative array of parameters for the signature
	 * @param string $strSecretKey
	 * @return string
	 */
	public function debug_only_SigStr (string $strScriptName, array $arrParams, string $strSecretKey ): string
    {
		return $this->makeSigStr($strScriptName, $arrParams, $strSecretKey);
	}


    /**
     * Make signature.
     * WARNING: This method can be used only for debugging purposes!
     *
     * @param string $strScriptName script name
     * @param array $arrParams  associative array of parameters for the signature
     * @param string $strSecretKey
     * @return string
     */
	private function makeSigStr ( string $strScriptName, array $arrParams, string $strSecretKey ): string {
		unset($arrParams['sig']);
		
		ksort($arrParams);

		array_unshift($arrParams, $strScriptName);
		$arrParams[] = $strSecretKey;

		return implode(';', $arrParams);
	}

	/********************** singing XML ***********************/

	/**
	 * make the signature for XML
	 *
     * @param string $strScriptName script name
	 * @param string|SimpleXMLElement $xml
	 * @param string $strSecretKey
	 * @return string
	 */
	public function makeXML (string $strScriptName, $xml, string $strSecretKey ): string
	{
		$arrFlatParams = $this->makeFlatParamsXML($xml);
		return $this->make($strScriptName, $arrFlatParams, $strSecretKey);
	}

	/**
	 * Verifies the signature of XML
	 *
     * @param string $strScriptName script name
	 * @param string|SimpleXMLElement $xml
	 * @param string $strSecretKey
	 * @return bool
	 */
	public function checkXML ( string $strScriptName, $xml, string $strSecretKey ): bool
	{
		if ( ! $xml instanceof \SimpleXMLElement ) {
			$xml = new \SimpleXMLElement($xml);
		}
		$arrFlatParams = $this->makeFlatParamsXML($xml);
		return $this->check((string)$xml->sig, $strScriptName, $arrFlatParams, $strSecretKey);
	}

	/**
	 * Returns a string, a hash of which coincide with the result of the makeXML() method.
	 * WARNING: This method can be used only for debugging purposes!
	 *
     * @param string $strScriptName script name
	 * @param string|SimpleXMLElement $xml
	 * @param string $strSecretKey
	 * @return string
	 */
	public function debug_only_SigStrXML ( string $strScriptName, $xml, string $strSecretKey )
	{
		$arrFlatParams = $this->makeFlatParamsXML($xml);
		return $this->makeSigStr($strScriptName, $arrFlatParams, $strSecretKey);
	}

	/**
	 * Returns flat array of XML params
	 *
	 * @param (string|SimpleXMLElement) $xml
     * @param string $parent_name name of the parent tag
	 * @return array
	 */
	private function makeFlatParamsXML ($xml, string $parent_name = '' ): array
	{
		if ( ! $xml instanceof \SimpleXMLElement ) {
			$xml = new \SimpleXMLElement($xml);
		}

		$arrParams = [];
		$i = 0;

		foreach ( $xml->children() as $tag ) {
			
			$i++;
			if ( 'sig' === $tag->getName() ) {
                continue;
            }
				
			/**
			 * Имя делаем вида tag001subtag001
			 * Чтобы можно было потом нормально отсортировать и вложенные узлы не запутались при сортировке
			 */
			$name = $parent_name . $tag->getName().sprintf('%03d', $i);

			if ( $tag->children() ) {
				$arrParams = array_merge($arrParams, $this->makeFlatParamsXML($tag, $name));
				continue;
			}

			$arrParams += array($name => (string)$tag);
		}



		return $arrParams;
	}

    /**
     * Returns flat array of XML params
     *
     * @param array $params
     * @param string $parent_name name of the parent tag
     * @return array
     */
    private function makeFlatParams ( array $params, string $parent_name = '' ): array
	{

		$arrParams = array();
		$i = 0;
		foreach ( $params as $var => $value ) {
			
			$i++;
			if ($var == 'sig') continue;
				
			/**
			 * Имя делаем вида tag001subtag001
			 * Чтобы можно было потом нормально отсортировать и вложенные узлы не запутались при сортировке
			 */
			$name = $parent_name . $var.sprintf('%03d', $i);

			if ( is_array($value)) {
				$arrParams = array_merge($arrParams, $this->makeFlatParams($value, $name));
				continue;
			}

			$arrParams += array($name => (string) $value);
		}

		return $arrParams;
	}

}
