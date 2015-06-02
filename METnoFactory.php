<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 */
interface METnoInterface {
    const DOWNLOAD_FAILED   = 100;
    const XML_INVALID       = 101;
    const DATA_EMPTY        = 102;
    const FORECAST_INVALID  = 103;
}

class METnoFactory implements METnoInterface {
    /**
     * class name for extending function for symbols. Sub class must extend
     * <METnoSymbol>
     * @var METnoSymbol
     */
    static protected $classSymbol               = "METnoSymbol";
    
    /**
     * class name for precipitation wich must subclass <METnoPrecipitation>
     * @var METnoPrecipitation 
     */
    static protected $classPrecipitation        = "METnoPrecipitation";
    
    /**
     * Defines hour wich will be used when selecting day forecast
     * - used only if <$dayForecastHighest> is false
     * - range of 0 - 23
     * @var int 
     */
    static protected $dayForecastByHour         = 14;
    
    /**
     * Defines hour wich will be used when selecting night forecast
     * - used only if <$nightForecastLowest> is false
     * - range of 0 - 23
     * @var int 
     */
    static protected $nightForecastByHour       = 23;
    
    /**
     * Defines hour wich defines when the night starts
     * - used only if <$nightForecastLowest> is true
     * - range of 0 - 23
     * @todo sunset time by location
     * @var int 
     */
    static protected $nightHourStart            = 20;
    
    /**
     * Weather forecast for all day is selected from the hightest
     * temperature
     * @var boolean 
     */
    static protected $dayForecastHighest        = true;
    
    /**
     * Weather forecast for night is selected from the lowest temperature 
     * @var boolean 
     */
    static protected $nightForecastLowest       = true;

    /**
     * Folder for caching by location and hour
     * @var string 
     */
    static protected $cacheDir                  = "_METcache/";
    
    /**
     * Count of decimals for rounding of wind speed
     * @var int 
     */
    static protected $decimalWindSpeed          = 2;
    /**
     * Count of decimals for rounding of percente values
     * @var int 
     */
    static protected $decimalPercente           = 0;
    
    /**
     * Count of decimals for rounding of temperature
     * @var int 
     */
    static protected $decimalTemperature        = 0;

    /**
     * Display error and stop php
     * @var boolean 
     */
    static protected $dieOnError                = false;
    
    /**
     * Display error and continue
     * @var boolean 
     */
    static protected $displayErrors             = false;

    /**
     * Display error and stop php
     * @param type $set
     */
    static public function setDieOnError($set = true) {
        self::$dieOnError = $set;
    }
    
    /**
     * Display error and continue
     * @param type $set
     */
    static public function setDisplayErrors($set = true) {
        self::$displayErrors = $set;
    }
    
    /**
     * Sets the hour for selecting day forecast (disables selecting by highest temperature)
     * @param int $hour - range of 0 - 23
     * @return boolean
     */
    static public function setHourForDayForecast($hour) {
        $hour   = intval($hour);
        
        if ($hour >= 0 && $hour <= 23) {
            self::$dayForecastHighest   = false;
            self::$dayForecastByHour    = $hour;
            return true;
        }
        
        return false;
    }
    
    /**
     * Sets the hour for selecting night forecast (disables selecting by lowest temperature)
     * @param int $hour - range of 0 - 23
     * @return boolean
     */
    static public function setHourForNightForecast($hour) {
        $hour   = intval($hour);
        
        if ($hour >= 0 && $hour <= 23) {
            self::$nightForecastLowest      = false;
            self::$nightForecastByHour      = $hour;
            return true;
        }
        
        return false;
    }
    
    /**
     * Defines when the night starts
     * @param int $hour - range of 0 - 23
     * @return boolean
     */
    static public function setHourWhenNightStarts($hour) {
        $hour   = intval($hour);
        
        if ($hour >= 0 && $hour <= 23) {
            self::$nightHourStart           = $hour;
            return true;
        }
        return false;
    }
    /**
     * Sets if the day forecast should be choosed by highest temperature or defined
     * hour <METnoFactory::setHourForDayForecast()>
     * @param type $set
     * @return boolean
     */    
    static public function setDetectDayForecastByTemperature($set = true) {
        self::$dayForecastHighest = $set;
        return true;
    }
    /**
     * Sets if the night forecast should be choosed by lowest temperature or defined
     * hour <METnoFactory::setHourForNightForecast()>
     * @param type $set
     * @return boolean
     */  
    static public function setDetectNightForecastByTemperature($set = true) {
        self::$nightForecastLowest = $set;
        return true;
    }
    
    /**
     * Sets a class name for symbol. Must be subclass of <METnoSymbol>
     * 
     * - the class must exists or it tries to load it in the same directory
     * 
     * @param type $class_name
     * @return boolean
     */
    static public function setSymbolClass($class_name) {     
        
        self::loadClass($class_name); // try to load class if not present
        
        if (class_exists($class_name)) {
            self::$classSymbol          = $class_name;
            return true;
        }
        return false;
    }
    
    /**
     * Sets a class name for precipitation. Must be subclass of <METnoPrecipitation>
     * 
     * - the class must exists or it tries to load it in the same directory
     * 
     * @param type $class_name
     * @return boolean
     */
    static public function setPrecipitationClass($class_name) {        
        if (!class_exists($class_name) && file_exists($class_name.".php")) {
            require_once $class_name.".php";
        }
        
        if (class_exists($class_name)) {
            self::$classPrecipitation  = $class_name;
            return true;
        }
        return false;
    }
    
    static protected function loadClass($class_name) {
        if (!class_exists($class_name)) {
            if (file_exists(__DIR__."/".$class_name.".php")) {
                require_once __DIR__."/".$class_name.".php";
            } else if (file_exists($class_name.".php")) {
                require_once $class_name.".php";
            }
        }
    }
    
    /**
     * Returns the number of decimals for windSpeed
     * @return boolean
     */
    static public function setWindSpeedDecimals($set) {
        self::$decimalWindSpeed  = $set;
        return true;
    }
    /**
     * Returns the number of decimals for percente values
     * @return boolean
     */
    static public function setPercenteDecimals($set) {
        self::$decimalPercente   = $set;
        return true;
    }
    
    /**
     * Returns the number of decimals for temperature
     * @return boolean
     */
    static public function setTemperatureDecimals($set) {
        self::$decimalTemperature    = $set;
        return true;
    }

    /**
     * Gets forecast for location defined by Lat and Lon
     * @param type $lat
     * @param type $lon
     * @param type $seeLevel
     * @return \METno
     */
    static public function getForecastByLatLon($lat, $lon, $seeLevel = false) {
        $yr = new METno($lat, $lon, $seeLevel);
        $yr->getForecast();
        return $yr;
    }
    
    /**
     * Gets forecast for the location defined by the adress using google geocoding
     * @todo
     * @param type $locationName
     * @return \METno
     */
    static public function getForecastByLocation($locationName) {
        $lat    = 0;
        $lon    = 0;
        $yr = new METno($lat, $lon);
        $yr->getForecast();
        return $yr;
    }

    /**
     * Returns only date 2012-08-27 from 2012-08-27T18:00:00Z
     * @param type $date Date in format 2012-08-27T18:00:00Z
     * @return boolean|string
     */
    static public function getDate($date) {
        
        if (preg_match("~([\d]{4})-([\d]{2})-([\d]{2})~", $date, $match)) {
            return $match[0];
        }
        return false;
    }
    /**
     * Returns only time 18:00 from 2012-08-27T18:00:00Z
     * @param type $date Date in format 2012-08-27T18:00:00Z
     * @return boolean|string
     */
    static public function getTime($date) {
        if (preg_match("~[\d]{4}-[\d]{2}-[\d]{2}T([\d]{2}):([\d]{2})~", $date, $match) && isset($match[1]) && isset($match[2])) {            
            return $match[1].":".$match[2];
        }
        return false;
    }
    /**
     * Returns only hour 18 from 2012-08-27T18:00:00Z
     * @param type $date Date in format 2012-08-27T18:00:00Z
     * @return boolean|int
     */
    static public function getHour($date) {
        if (preg_match("~[\d]{4}-[\d]{2}-[\d]{2}T([\d]{2}):[\d]{2}~", $date, $match) && isset($match[1])) {            
            return intval($match[1]);
        }
        return false;
    }
    /**
     * Checks in attributes array if there is an attribute key and returns string
     * or float with defined decimals
     * @param SimpleXMLElement $attributes
     * @param string $attributeKey
     * @param int $floatValAndRoundByDecimals if defined, the value is floated and
     * rounded with defined decimals
     * @return int|boolean|string
     */
    static public function getAttributeValue(SimpleXMLElement $attributes,$attributeKey,$floatValAndRoundByDecimals = -1) {
        if (isset($attributes[$attributeKey])) {
            $value      = $attributes[$attributeKey]->__toString();
            if ($floatValAndRoundByDecimals != -1) {
                $value  = round(floatval($value),$floatValAndRoundByDecimals);
            }
            return $value;
        } else if ($floatValAndRoundByDecimals != -1) {
            return 0;
        }
        return false;
    }
    
    /**
     * Returns the number of decimals for windSpeed
     * @return int
     */
    static public function getWindSpeedDecimals() {
        return self::$decimalWindSpeed;
    }
    /**
     * Returns the number of decimals for percente values
     * @return int
     */
    static public function getPercenteDecimals() {
        return self::$decimalPercente;
    }
    
    /**
     * Returns the number of decimals for temperature
     * @return int
     */
    static public function getTemperatureDecimals() {
        return self::$decimalTemperature;
    }
    
    /**
     * Get an entry of forecast by offset hour, first look in forecast Array with
     * hour - offset, if not set, find hour + offset, if not found, increse offset +1
     * and start again. Max loops are 10, then boolean returned
     * @param type $hour
     * @param type $forecastArrayByHour
     * @param type $offset
     * @return boolean|SimpleXMLElement
     */
    static public function getNearestForecastForHour($hour,$forecastArrayByHour,$offset = 1) {
        $prevHour   = $hour - $offset;
        $nextHour   = $hour + $offset;
        
        if (isset($forecastArrayByHour[$nextHour])) {
            return $forecastArrayByHour[$nextHour];
        }
        
        if (isset($forecastArrayByHour[$prevHour])) {
            return $forecastArrayByHour[$prevHour];
        }
        
        if ($offset == 24) {
            return false;
        }
        
        return self::getNearestForecastForHour($hour, $forecastArrayByHour, $offset+1);
    }
    
    /**
     * Get hour for day forecast
     * @return int
     */
    static public function getHourForDayForecast() {
        return self::$dayForecastByHour;
    }
    
    /**
     * Returns hour for night forecast
     * @return int
     */
    static public function getHourForNightForecast() {        
        return self::$nightForecastByHour;
    }
    
    /**
     * Gets the hour when night starts
     * @return int
     */
    static public function getHourWhenNightStarts() {
        return self::$nightHourStart;
    }
    
    /**
     * Should the day info from the highest temperature?
     * @return int
     */
    static public function isDayForecastByHighestTemp() {
        return self::$dayForecastHighest;
    }
    /**
     * Should the night info from the lowest temperature?
     * Night detection is defined by the hour wich the night starts
     * <METnoFactory::getHourWhenNightStarts()>
     * @return int
     */
    static public function isNightForecastByLowestTemp() {
        return self::$nightForecastLowest;
    }
    
    
    
}
?>
