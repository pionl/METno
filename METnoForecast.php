<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 */

class METnoForecast {
    private $parent                     = false;
    
    /**
     *
     * @var METnoSymbol 
     */
    protected $symbol                   = 0;
   
    
    protected $date                     = "";
    protected $hour                     = "";
            
    /**
     * Temperature in celcius
     * @var type 
     */
    protected $temperature              = 0;
    
    /**
     * Wind speed in m/s
     * @var decimal 
     */
    protected $windSpeed                = 0;
    
    protected $windDegrees              = 0;
    
    protected $windOrientation          = "NONE";
    
    /**
     * Precipitation (srážky) in mm
     * @var type 
     */
    protected $precipitation            = 0;
    
    protected $precipitationInHours     = array();


    /**
     * Humidity (vlhkost) in percente
     * @var int 
     */
    protected $humidity                 = 0;
           
    /**
     * Pressure in hPa (default)
     * 
     * @var type 
     */
    protected $pressure                 = 0;
    protected $pressureUnit             = "hPa";


    /**
     * Fog in percente
     * @var int 
     */
    protected $fog                      = 0;
    
    protected $cloudiness               = 0;
    
    protected $lowClouds                = 0;
    
    protected $mediumClouds             = 0;
    
    protected $highClouds               = 0;    
    
    
    public function __construct(METnoDay $parent,$date,$hour,SimpleXMLElement $mainXMLElement,$symbolsArray) {
        $this->parent   = $parent;
        
        $this->date     = $date;
        $this->hour     = $hour;        
        
        /**
         * Get all the datas from main XML element - weather info (detail)
         */
        
        if (isset($mainXMLElement->temperature)) {
            $this->temperature      = METnoFactory::getAttributeValue($mainXMLElement->temperature->attributes(), "value",  METnoFactory::getTemperatureDecimals());
        }
        
        if (isset($mainXMLElement->windSpeed)) {
            $this->windSpeed        = METnoFactory::getAttributeValue($mainXMLElement->windSpeed->attributes(), "value",  METnoFactory::getWindSpeedDecimals());
        }
        
        if (isset($mainXMLElement->windDirection)) {
            $attribtues             = $mainXMLElement->windDirection->attributes();
            $this->windDegrees      = METnoFactory::getAttributeValue($attribtues, "deg",0);
            $this->windOrientation  = METnoFactory::getAttributeValue($attribtues, "name");
        }
        
        if (isset($mainXMLElement->humidity)) {
            $this->humidity         = METnoFactory::getAttributeValue($mainXMLElement->humidity->attributes(), "value",  METnoFactory::getPercenteDecimals());
        }
        
        if (isset($mainXMLElement->pressure)) {
            $attribtues             = $mainXMLElement->pressure->attributes();
            $this->pressure         = METnoFactory::getAttributeValue($attribtues, "value",1);
            $this->pressureUnit     = METnoFactory::getAttributeValue($attribtues, "unit");
        }
        
        if (isset($mainXMLElement->cloudiness)) {
            $this->cloudiness       = METnoFactory::getAttributeValue($mainXMLElement->cloudiness->attributes(), "percent",  METnoFactory::getPercenteDecimals());
        }
        
        if (isset($mainXMLElement->fog)) {
            $this->fog              = METnoFactory::getAttributeValue($mainXMLElement->fog->attributes(), "percent",  METnoFactory::getPercenteDecimals());
        }
        
        if (isset($mainXMLElement->lowClouds)) {
            $this->lowClouds        = METnoFactory::getAttributeValue($mainXMLElement->lowClouds->attributes(), "percent",  METnoFactory::getPercenteDecimals());
        }
        
        if (isset($mainXMLElement->mediumClouds)) {
            $this->mediumClouds     = METnoFactory::getAttributeValue($mainXMLElement->mediumClouds->attributes(), "percent",  METnoFactory::getPercenteDecimals());
        }
        
        if (isset($mainXMLElement->highClouds)) {
            $this->highClouds       = METnoFactory::getAttributeValue($mainXMLElement->highClouds->attributes(), "percent",  METnoFactory::getPercenteDecimals());
        }
        
        /**
         * Select symbol and precipitation from the nearest record and prepare
         * stats by 2 hours, 3 hours, 6 hours (difference)
         * @uses METnoSymbol
         */
        
        if (!empty($symbolsArray)) {
            $first  = true;
            foreach ($symbolsArray as $symbol) {
                
                if ($first) {
                    $this->precipitation    = $symbol["precipitation"];
                    $this->symbol           = $symbol["symbol"]->setWeather($this);                    
                    $first                  = false;
                } else {
                    $this->precipitationInHours[$symbol["difference"]]  = $symbol["precipitation"];
                }                
            }
        }  
    }
    
    public function __toString() {
        return "$this->temperature";
    }
    
    public function isNight() {
        return $this->hour >= METnoFactory::getHourForNightForecast();
    }
    
    /**
     * 
     * @return METnoDay
     */
    public function getMODay() {
        return $this->parent;
    }
    
    public function getDate() {
        return $this->date;
    }
    
    public function getTime() {
        return $this->hour.":00";
    }
    
    public function getHour() {
        return $this->hour;
    }
    
    public function getTemperature() {
        return $this->temperature;
    }
    
    /**
     * Returns the symbol for the weather
     * @return METnoSymbol
     */
    public function getSymbol() {
        return $this->symbol;
    }
    
    
}
?>
