# MET.no forecast library

Small library for getting forecast for given coordinates. Updated to newest API, originally made in 2012.

Endpoint: http://api.met.no/weatherapi/
Endpoint version: 1.8

## Usage

Basic usage you can find in example.php

You can install this library via composer or download the classes and to load all the classes you can use loader_METno.php

Add to composer require

	"pion/metno": ">=1.0.0"

## METnoDay

This class represents the forecast for the day. It has several properties you can access. By default the symbol and values is taken from the highest temperature, you can also set the hour which should be used.

The day has also forecast for every day (represented by METnoForecast class). When API has missing hour, the parsing will ensure that the hour will be filled by previous/next value.

Also you can access night forecast, hour forecast for highest or lowest temperature.

## Custom settings

All custom settings can be made in METnoFactory via setters. You can change the class of the symbol (representing the forecast icon) and percipitation (representing values)

## TODO

Possible features that could be made

### METno
- info abaout location, should be added to METnoDay too
- time by location
- detection of night by sunset (currently detection is made by hour)

### METnoDay
- mornig forecast
- correct hour symbol from the form


