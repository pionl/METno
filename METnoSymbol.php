<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 * 
 * @uses METnoForecast Description
 */

class METnoSymbol implements JsonSerializable {
    /**
     * For detection of day progress (night)
     * @var METnoForecast 
     */
    protected $weather;
    
    protected $name         = "NONE";
    protected $number       = 1;
    
    protected $imageUrl     = "http://api.met.no/weatherapi/weathericon/1.1/?symbol={code};content_type=image/png";
            
    public function __construct($number,$name) {
        $this->name     = $name;
        $this->number   = $number;
    }
    
    public function setWeather(METnoForecast $weather) {
        $this->weather  = $weather;
        return $this;
    }
    
    public function getNumber() {
        return $this->number;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getUrl() {
        $url    = str_replace("{code}",$this->number,$this->imageUrl);
        /**
         * Detects if its night and show the right symbol
         */
        if ($this->isNight()) {
            $url.=";is_night=1";
        }
        
        return $url;
    }
    
    protected function isNight() {
        return is_object($this->weather) && is_object($this->weather->getMODay()) && $this->weather->isNight();
    }
    
    public function getHTML() {
        return "<img src='".$this->getUrl()."' alt='".$this->name."'/>";
    }
    
    public function __toString() {
        return $this->getUrl();
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return array(
            "id" => $this->getNumber(),
            "name" => $this->getName(),
            "is" => array(
                "night" => $this->isNight()
            ),
            "url" => $this->getUrl()
        );
    }

}
