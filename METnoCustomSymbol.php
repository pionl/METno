<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 * 
 * Symbol documentation (string in lower case): 
 * @link http://api.met.no/weatherapi/weathericon/1.0/documentation
 */

class METnoCustomSymbol extends METnoSymbol {
    static protected $fileFormat    = ".png";    
    
    /**
     * Sets file format (extension) without dot
     * @param type $fileFormat
     */
    static public function setFileFormat($fileFormat) {
        if ($fileFormat != "") {
            self::$fileFormat = ".$fileFormat";
        } else {
            self::$fileFormat = $fileFormat;
        }
    }
        
    /**
     * Returns global file format for icon
     * @return type
     */
    static public function getFileFormat() {
        return self::$fileFormat;
    }
    
    /**
     * Return url of the image with defined file format
     * 
     * @link http://api.met.no/weatherapi/weathericon/1.0/documentation
     * @return string NUMBER-LOWERED_NAME.FILE_FORMAT
     */
    public function getUrl() {
        return $this->number."-".strtolower($this->name).METnoCustomSymbol::getFileFormat();
    }    
}
?>
