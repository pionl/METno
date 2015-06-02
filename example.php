<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 * This software cannot be copied/edited or redistributed without permission by iMakers, s.r.o.
 */

//require_once 'vendor/autoload.php';
require_once 'loader_METno.php';

METnoFactory::setHourForDayForecast(14);
METnoFactory::setTemperatureDecimals(1);

$forecastBrno   = METnoFactory::getForecastByLatLon(49.199205, 16.598866);

if ($forecastBrno->isSuccess()) {
    
    
    echo $forecastBrno->today()->getTemperature();
    echo $forecastBrno->today()->getSymbol()->getHTML();
    
    echo "<br/>";
    echo $forecastBrno->tomorrow()."/".$forecastBrno->tomorrow()->getNightForecast().$forecastBrno->tomorrow()->getSymbol()->getHTML();
    echo "<br/>";
    echo $forecastBrno->in2Days()."/".$forecastBrno->in2Days()->getNightForecast();
} else echo $forecastBrno->getErrorHTML();


// forecast in loop where you get desired days
// example using custom symbol in own directory
// same naming as the MET.no icons
// you need to set the custom symbol class (or create own)

METnoFactory::setSymbolClass("METnoCustomSymbol");

$forecastBrnoCustom   = METnoFactory::getForecastByLatLon(49.199205, 16.598866);
$forecast = $forecastBrno2->getForecastForXDays(5);

foreach ($forecast as $day) {
	$iconPath = "img/weather/".$day->getSymbol();
	$temp = $day->getTemperature();
	$date = $day->getDate();
}

?>
