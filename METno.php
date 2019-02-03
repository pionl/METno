<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 *
 *
 *
 * @todo info about location, should be added to METnoDay too
 * @todo time by location
 * @todo detection of night by sunset
 *
 * @uses METnoFactory
 * @uses METnoDay
 * @uses METnoSymbol
 * @uses METnoPrecipitation
 */
class METno extends METnoFactory {

    protected $apiRequest       = "https://api.met.no/weatherapi/locationforecast/1.9/?";
    protected $apiParameters    = "";

    /**
     * If error has occurred, the exception object is saved here
     * @var Exception
     */
    protected $error            = false;
    protected $errorHTML        = "";

    protected $forecastByDay    = array();

    /**
     * Constructs METno class with specified $lat $lon location and option seelevel
     *
     * @param <decimal>         $lat
     * @param <decimal>         $lon
     * @param <int|boolean>     $seeLevel - meters
     */
    public function __construct($lat, $lon, $seeLevel = false) {
        $this->apiParameters       .= "lat=$lat&lon=$lon";
        if (!is_bool($seeLevel)) {
            $this->apiParameters   .= "&msl=$seeLevel";
        }
    }

    /**
     * Sends CURL request and returns the content if no error
     * @param type $url
     * @return boolean|string
     * @throws Exception
     */
    protected function sendRequest($url) {

        try {

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.001 (windows; U; NT4.0; en-US; rv:1.0) Gecko/25250101');

            $content = curl_exec($curl);

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
                curl_close($curl);
                return $content;
            } else {
                curl_close($curl);
                throw new Exception("Error with downloading file from $url with HTTP Code: $httpCode", METno::DOWNLOAD_FAILED);
            }
        } catch (Exception $e) {
            return $this->error($e);
        }
        return false;
    }

    /**
     * Genereates HTML Error and displays it if die on error is active
     * If die on error is not active, the html and Exception is saved for
     * internal use
     * @param <Exception> $e
     * @return boolean
     */
    public function error(Exception $e) {
        $this->errorHTML = "<h2>METno - An error has occurred</h2>";
        $this->errorHTML .= "<table>";
        $this->errorHTML .= "<tr><td style='width: 100px;padding-right: 10px;text-align:right;'>File </td><td> " . $e->getFile() . ":<strong>" . $e->getLine() . "</strong></td></tr>";
        $this->errorHTML .= "<tr><td style='padding-right: 10px;text-align:right;'>Code </td><td> " . $e->getCode() . "</td></tr>";
        $this->errorHTML .= "<tr><td style='padding-right: 10px;text-align:right;'>Message </td><td> " . $e->getMessage() . "</td></tr>";
        $this->errorHTML .= "</table><h3>Stack trace</h3>";

        foreach ($e->getTrace() as $trace) {
            $this->errorHTML        .="<p>";
            if (isset($trace["class"]) && $trace['class'] != '') {
                $this->errorHTML    .= $trace['class'];
                $this->errorHTML    .= '->';
            }

            $this->errorHTML        .= $trace['function'];
            $this->errorHTML        .= '(';
            if (!empty($trace["args"])) {
                $first  = true;

                foreach($trace["args"] as $argument) {
                    if (is_string($argument)) {
                        if ($first) {
                            $first  = false;
                        } else {
                            $this->errorHTML.=",";
                        }
                        $this->errorHTML.= $argument;
                    }
                }
            }
            $this->errorHTML        .= ');<br />';
        }
        $this->errorHTML .= "</table>";

        if (self::$dieOnError) {
            header("Content-type: text/html; charset=utf-8");
            die($this->errorHTML);
        } else {
            $this->error = $e;

            if (self::$displayErrors) {
                header("Content-type: text/html; charset=utf-8");
                echo $this->errorHTML;
            }
        }

        return false;
    }

    /**
     * Detects if there was an error during parsing xml
     * @return type
     */
    public function isError() {
        return is_object($this->error);
    }

    public function isSuccess() {
        return !is_object($this->error);
    }

    /**
     * Return today forecast wich can be printed/echo to get current temperature
     * @return METnoForecast
     */
    public function today() {
        return $this->getForecastForDate(date("Y-m-d"));
    }

    /**
     * Return tomorrows forecast wich can be printed/echo to get current temperature
     * @return METnoForecast
     */
    public function tomorrow() {
        return $this->getForecastForDate(date("Y-m-d", strtotime("+1 DAY")));
    }

    /**
     * Return forecast in 2 days wich can be printed/echo to get current temperature
     * @return METnoForecast
     */
    public function in2Days() {
        return $this->getForecastForDate(date("Y-m-d",  strtotime("+2 DAY")));
    }

    /**
     * Return forecast in 3 days wich can be printed/echo to get current temperature
     * @return METnoForecast
     */
    public function in3Days() {
        return $this->getForecastForDate(date("Y-m-d",  strtotime("+3 DAY")));
    }

    /**
     * Return forecast in 4 days wich can be printed/echo to get current temperature
     * @return METnoForecast
     */
    public function in4Days() {
        return $this->getForecastForDate(date("Y-m-d",  strtotime("+4 DAY")));
    }

    /**
     * Return forecast in 5 days wich can be printed/echo to get current temperature
     * @return METnoForecast
     */
    public function in5Days() {
        return $this->getForecastForDate(date("Y-m-d",  strtotime("+5 DAY")));
    }

    public function getForecastForXDays($count) {

        $current    = 0;
        $forecast   = array();

        foreach ($this->forecastByDay as $date => $forecastForDay) {
            $forecast[$date]     = $forecastForDay;
            $current++;
            if ($current == $count) {
                break;
            }
        }

        return $forecast;
    }

    /**
     * < GEThers >
     */

    /**
     * Returns Exception object if an error has occurred
     * @return <Exception>|boolean
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Returns and generated HTML with error details
     * @return string
     */
    public function getErrorHTML() {
        return $this->errorHTML;
    }

    /**
     * Returns error message from <Exception> object
     * @return string
     */
    public function getErrorMesssage() {
        if ($this->isError()) {
            return $this->error->getMessage();
        } else {
            return "No error has occurred";
        }
    }

    /**
     *
     * @return boolean
     * @throws Exception
     */
    public function getForecast() {
        if (!empty($this->forecastByDay)) {
            return $this->forecastByDay;
        }

        $xml    = $this->getForecastXML();

        if (!is_bool($xml)) {
            try {

                /**
                 * Container with time entries by day
                 */
                $forecastByDay = array();
                $previousDay = null;
                $lastForeCast = null;

                foreach ($xml->product->time as $forecast) {

                    $forecastAttributes                     = $forecast->attributes();
                    if (isset($forecast->location)) {

                        if (isset($forecastAttributes["datatype"]) && isset($forecastAttributes["from"]) && $forecastAttributes["to"]) {
                            $fromValue                              = self::getAttributeValue($forecastAttributes, "from");
                            $toValue                                = self::getAttributeValue($forecastAttributes, "to");

                            if (!is_bool($fromValue) && !is_bool($toValue)) {

                                $fromDate                           = self::getDate($fromValue);
                                $fromHour                           = self::getHour($fromValue);
                                $toHour                             = self::getHour($toValue);


                                if (!is_bool($fromDate) && !is_bool($toHour) && !is_bool($fromHour)) {

                                    // check the previus day data if have all
                                    if (!is_null($previousDay) && $previousDay != $fromDate) {
                                        $hasData = false;

                                         foreach ($forecastByDay[$previousDay] as $hour) {
                                             if (is_object($hour["detail"])) {
                                                 $hasData = true;
                                             }
                                         }

                                         if (!$hasData) {
                                             // add to last hour the forecast location
                                             $hour = key($forecastByDay[$previousDay]);
                                             $forecastByDay[$previousDay][$hour]["detail"] = $lastForeCast->location;
                                         }
                                    }

                                    /**
                                     * Prepare containers by date and then for hour
                                     */
                                    if (!isset($forecastByDay[$fromDate])) {
                                        $forecastByDay[$fromDate]   = array();
                                    }

                                    if (!isset($forecastByDay[$fromDate][$toHour])) {
                                        $forecastByDay[$fromDate][$toHour] = array(
                                            "detail"    => 0,
                                            "symbols"   => array()
                                        );
                                    }

                                    if (!isset($forecastByDay[$fromDate][$fromHour])) {
                                        $forecastByDay[$fromDate][$fromHour] = array(
                                            "detail"    => 0,
                                            "symbols"   => array()
                                        );
                                    }

                                    /**
                                     * Detect if the element is weather info or
                                     * symbol info and insert it into the correct containe
                                     */
                                    if ($fromHour == $toHour) {
                                        $forecastByDay[$fromDate][$toHour]["detail"]        = $forecast->location;
                                    } else {
                                        $difference     = 0;

                                        if ($toHour >= $fromHour) {
                                            $difference = $toHour - $fromHour;
                                        } else {
                                            $difference = (24 - $fromHour) + $toHour;
                                        }

                                        if (!isset($forecast->location->precipitation) || !isset($forecast->location->symbol)) {
                                            throw new Exception("The XML is invalid - precipitation or symbol not defined", METno::XML_INVALID);
                                        }

                                        $symbolAttributes           = $forecast->location->symbol->attributes();
                                        $precipitationAttributes    = $forecast->location->precipitation->attributes();

                                        $forecastByDay[$fromDate][$fromHour]["symbols"][$toHour]     = array(
                                            "from"          => $fromHour,       // from hour
                                            "to"            => $toHour,         // to hour
                                            "difference"    => $difference,     // difference in hours betwen from to to
                                            "symbol"        => new self::$classSymbol(self::getAttributeValue($symbolAttributes, "number",0),
                                                                                     self::getAttributeValue($symbolAttributes, "id")),
                                            "precipitation" => new self::$classPrecipitation(self::getAttributeValue($precipitationAttributes, "value",1),
                                                                                     self::getAttributeValue($precipitationAttributes, "minvalue",1),
                                                                                     self::getAttributeValue($precipitationAttributes, "maxvalue",1))
                                        );

                                    }

                                    $previousDay = $fromDate;
                                    $lastForeCast = $forecast;

                                } else throw new Exception("The XML is invalid - the date detection failed", METno::XML_INVALID);
                            } else throw new Exception("The XML is invalid - the from and to attribute is invalid", METno::XML_INVALID);
                        } else throw new Exception("The XML is invalid - time node attributes", METno::XML_INVALID);
                    } else throw new Exception("The XML is invalid - location node is missing", METno::XML_INVALID);
                }

                $previousDay = null;
                $forecast = null;

                if (!empty($forecastByDay)) {
                    // sort by date, the xml can return today date before yesterday
                    ksort($forecastByDay);

                    // Create days
                    foreach ($forecastByDay as $date => $forecast) {
                        $this->forecastByDay[$date] = new METnoDay($date, $forecast,$this);
                    }
                    return $this->forecastByDay;
                } else throw new Exception("The weather information is empty", METno::DATA_EMPTY);

                return $this->forecastByDay;
            } catch (Exception $exc) {
                return $this->error($exc);
            }
        }
        return false;
    }

    /**
     * Gets forecast XML by location and detects if the base structure is valide
     * and returns it for other use
     * @return boolean - if the xml is valide and download progress is success
     * @throws Exception
     */
    protected function getForecastXML() {

        try {
            $cacheSubFolder     = date("Ymd")."/";
            $cacheFileName      = self::$cacheDir.$cacheSubFolder.$this->apiParameters."-".date("H").".xml"; // prepare name of cache file by hour

            if (file_exists($cacheFileName)) {
                $xml            = simplexml_load_file($cacheFileName);
            } else {

                $apiResponse    = $this->sendRequest($this->apiRequest.$this->apiParameters); // send request to api

                if (is_bool($apiResponse)) { // failed to download file and on die is false
                    return false;
                }

                if (!is_dir(self::$cacheDir)) { // cache folder is not created
                    mkdir(self::$cacheDir);
                }

                if (!is_dir(self::$cacheDir.$cacheSubFolder)) {
                    mkdir(self::$cacheDir.$cacheSubFolder);
                }

                $xml            = simplexml_load_string($apiResponse);

                /**
                 * Create hour cache file and delete previous cache file HOUR - 1
                 */
                $cache          = fopen($cacheFileName, "w");

                fwrite($cache, $apiResponse);
                fclose($cache);

                // remove the previous hour
                $previousHour   = date("H",strtotime("-1 HOUR"));

                $cacheFileOld   = self::$cacheDir.$cacheSubFolder.$this->apiParameters."-".$previousHour.".xml";

                if (file_exists($cacheFileOld)) {
                    @unlink($cacheFileOld);
                }

                // remove the previous day

                $cacheSubFolder     = date("Ymd",strtotime("-1 DAY"))."/";

                if (is_dir(self::$cacheDir.$cacheSubFolder)) {
                    $this->rmdirRecursively(self::$cacheDir.$cacheSubFolder);
                }
            }

            if (!is_bool($xml) && isset($xml->product) && is_object($xml->product) && isset($xml->product->time)) {
                return $xml;
            } else {
                throw new Exception("The XML is invalid", METno::XML_INVALID);
            }
        } catch (Exception $e) {
            return $this->error($e);
        }
    }

    /**
     * Removes all the contents of dir
     * @param $dir
     * @return bool
     */
    public function rmdirRecursively($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? rmdirRecursively("$dir/$file") : @unlink("$dir/$file");
        }
        return @rmdir($dir);
    }

    /**
     *
     * @param type $date date in Y-m-d format
     * @return METnoForecast
     */
    public function getForecastForDate($date) {
        if (empty($this->forecastByDay)) {
            $this->getForecast();
        }

        if (isset($this->forecastByDay[$date])) {
            return $this->forecastByDay[$date];
        }

        return $this->error(new Exception("Forecast for date $date doesn't exist", METno::DATA_EMPTY));
    }
}

?>
