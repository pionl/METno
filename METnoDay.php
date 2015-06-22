<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 * 
 * 
 * @todo Morning forecast
 * @todo correct hour symbol from different symbol form to
 */

class METnoDay extends METnoForecast {
    /**
     * For internal work with main METno instance (about location etc)
     * @var METno 
     */
    protected $metNo                    = false;     
    
    protected $today                    = false;
    
    protected $hourWeather              = array();    
    
    /**
     * Forecast for the night, selected from the lowest temperature in night
     * Hour > 21:00 or by the time is setted (def 23)
     * 
     * From these props is detection made:
     * <METnoFactory::$nightForecastByHour> 
     * <METnoFactory::$nightForecastLowest> 
     * 
     * @var METnoForecast 
     */
    protected $nightForecast;        
    
    /**
     * Forecast for the hour where the temperature is highest
     * 
     * @var METnoForecast 
     */
    protected $hourForecastForHighestTemperature;
    
    /**
     * Forecast for the hour where the temperature is lowest
     * 
     * @var METnoForecast 
     */
    protected $hourForecastForTheLowestTemperature;    
    
    public function __construct($date,$arrayOfHours,$metNo = false) {
        $this->date     = $date;
        $this->metNo    = $metNo;
        
        /**
         * We must fill the hours with the closest time entry of weather symbol
         * and weather info.
         * 
         * if the weather info is not defined is hour with symbols, we fill find
         * the nearest weather info the symbol details
         * 
         * - This happens only on today entries
         * 
         * - first fill hours that have detail
         */
        
        $cleanForecastByHour                = array();
        $otherForecastByHour                = array();
        
        $nearestForecastHour                = 24;
        $nearestForecast                    = false;
        
        foreach ($arrayOfHours as $hour => $hourForecast) {
            if (is_object($hourForecast["detail"])) {
                
                $cleanForecastByHour[$hour] = $hourForecast;
                
                if ($hour < $nearestForecastHour) {
                    $nearestForecastHour    = $hour;
                    $nearestForecast        = $hourForecast["detail"];
                }
            } else {
                $otherForecastByHour[$hour] = $hourForecast["symbols"];
            }                        
        }
        /**
         * Fill hour forecast with symbol only entries and select whethear info
         * by lowest hour
         */
        
        if (!empty($otherForecastByHour) && is_object($nearestForecast)) {
            
            foreach ($otherForecastByHour as $hour => $hourSymbols) {
                if (!isset($cleanForecastByHour[$hour]) && !empty($hourSymbols)) {
                    
                    $cleanForecastByHour[$hour]     = array(
                        "detail"    => $nearestForecast,
                        "symbols"   => $hourSymbols
                    );
                    
                } else if (!empty($hourSymbols)) { 
                    // merge if need, not verified, just protection
                    $cleanForecastByHour[$hour]["symbols"] = array_merge($cleanForecastByHour[$hour]["symbols"],$hourSymbols);
                }
            }
        }        
        
        /**
         * Now when we are sure that all hours or part of them are cleaned and
         * compine forecast for hours that are not setted
         */
      
        $this->today                = $this->date == date("Y-m-d");
        
        $startHour                  = 0; // start from the 0 hour
        
        if ($this->today) { // fill only hours that will be in future, start on first filled hour
            $startHourKeys  = array_keys($cleanForecastByHour);
                $startHour              = reset($startHourKeys);
        }
        
        for ($hour = $startHour; $hour < 24; $hour++) {
            
            if (!isset($cleanForecastByHour[$hour])) {
                /** 
                 * Search for the closest forecast and duplicate the information for the hour
                 */                
                $hourForecast    = METnoFactory::getNearestForecastForHour($hour, $cleanForecastByHour);
                
                if (is_bool($hourForecast)) {
                    if (is_object($this->metNo)) {
                        $this->metNo->error(new Exception("Could not find nereast forecast, this should not happen.", METno::FORECAST_INVALID));
                        continue;
                    } else {
                        die("Could not find nereast forecast, this should not happen. Code: ".METno::FORECAST_INVALID);
                    }
                }
                
                
            } else {
                $hourForecast   = $cleanForecastByHour[$hour];
            }
            
            $this->hourWeather[$hour]                       = new METnoForecast($this,$this->date,$hour,$hourForecast["detail"],$hourForecast["symbols"]);       
            
            /**
             * get the hightest and lowest temperature and the night forecast if
             * needed. We need only hour forecast wich were defined in xml
             */
            
            if (isset($cleanForecastByHour[$hour])) {
                /**
                 * Get the highest and the lowest temperature for the day
                 */
                $this->hourForecastForHighestTemperature         = $this->getHourForecastForTemperature($this->hourForecastForHighestTemperature, $this->hourWeather[$hour]);
                
                $this->hourForecastForTheLowestTemperature      = $this->getHourForecastForTemperature($this->hourForecastForTheLowestTemperature, $this->hourWeather[$hour],false);

                /**
                 * Get the night forecast if the detection is by the lowest temperature in night
                 * Night detection is made by hour METnoFactory::getNightStartHour()
                 */
                if (METnoFactory::isNightForecastByLowestTemp() && METnoFactory::getHourWhenNightStarts() <= $this->hourWeather[$hour]->getHour()) {                    
                    $this->nightForecast      = $this->getHourForecastForTemperature($this->nightForecast, $this->hourWeather[$hour],false);
                }
            }
        }
        
        /**
         * Get the night forecast from setted hour if the night forecast is not made 
         * by the lowest temperature of the night <METnoFactory::getHourForNightForecast()>
         */
        if (!METnoFactory::isNightForecastByLowestTemp()) {
            $this->nightForecast    = $this->hourWeather[METnoFactory::getHourForNightForecast()];
        }
        
        /**
         * We need to create weather info for the whole day so we will 
         * take for the highest temperature for the day if defined in
         * <METnoFactory::isDayForecastByHighestTemp()> or from 
         * specified hour <METnoForecast::getHourForDayForecast()>
         * 
         */
        
        if ($this->today) { // dont take this day
            $weatherToCopy      = reset($this->hourWeather);            
        } else {            
            if (METnoFactory::isDayForecastByHighestTemp()) {
                $weatherToCopy  = $this->hourForecastForHighestTemperature;
            } else if (isset($this->hourWeather[METnoFactory::getHourForDayForecast()])) {
                $weatherToCopy  = $this->hourWeather[METnoFactory::getHourForDayForecast()];
            }
        }
        /**
         * Copy the properties of the forecast to the day forecast properities
         */
        if (isset($weatherToCopy) && is_object($weatherToCopy)) {
            foreach ($weatherToCopy as $property => $value) {
                if ($property != "parent") {
                    $this->$property     = $value;
                }
            }
        }
    }   
    
    public function isToday() {
        return $this->today;
    }
    
    /**
     * Returns self becouse of overiding the hour forecast
     * @return \METnoDay
     */
    public function getMETDay() {
        return $this;
    }
    
    /**
     * Return the hour forecast if exist, if not, return self
     * @param int $hour
     * @return METnoForecast
     */
    public function getForecastForHour($hour) {
        if (isset($this->hourWeather[$hour])) {
            return $this->hourWeather[$hour];
        } else {
            return $this;
        }        
    }
    /**
     * Gets the night forecast
     * @return METnoForecast
     */
    public function getNightForecast() {
        return $this->nightForecast;
    }
    
    /**
     * Gets the lowest temperature of the day
     * @return METnoForecast
     */
    public function getLowestTemperature() {
        return $this->getLowestTemperatureForecast()->getTemperature();
    }
    
    /**
     * Gets the hour forecast with lowest temperature of the day
     * @return METnoForecast
     */
    public function getLowestTemperatureForecast() {
        return $this->hourForecastForTheLowestTemperature;
    }
    
    /**
     * Gets the highest temperature of the day
     * @return METnoForecast
     */
    public function getHighestTemperature() {
        return $this->getHighestTemperatureForecast()->getTemperature();
    }
    
    /**
     * Gets the hour forecast with highest temperature of the day
     * @return METnoForecast
     */
    public function getHighestTemperatureForecast() {
        return $this->hourForecastForHighestTemperature;
    }
    
    /**
     * Detects wich hour forecast (METnoForecast) has the highest or lowest temperature,
     * 
     * If the instance is false, return instanceForCheck
     * 
     * @param METnoForecast|boolean $instance
     * @param METnoForecast $instanceForCheck
     * @param boolean $higher defines if the higher temperature forecast should be returned
     * @return METnoForecast
     */
    protected function getHourForecastForTemperature($instance,METnoForecast $instanceForCheck,$higher = true) {
        if (is_object($instance)) {
            if ($higher) {
                if ($instance->getTemperature() < $instanceForCheck->getTemperature()) {
                    return $instanceForCheck;
                }
            } else if ($instance->getTemperature() > $instanceForCheck->getTemperature()) {
                return $instanceForCheck;
            }            
        } else {
            return $instanceForCheck;
        }
        return $instance;
    }
    
}
?>
