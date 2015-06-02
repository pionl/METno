# MET.no forecast library

Small library for getting forecast for given coordinates. Updated to newest API, originally made in 2012.

Endpoint: http://api.met.no/weatherapi/
Endpoint version: 1.8

## Usage

Basic usage you can find in example.php

You can install this library via composer or download the classes and to load all the classes you can use loader_METno.php

Add to composer require

	"pion/metno": ">=1.0.0"


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


