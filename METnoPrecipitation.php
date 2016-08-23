<?php

/**
 * @author Martin Kluska @ iMakers, s.r.o. <martin.kluska@imakers.cz>
 * @copyright iMakers, s.r.o.
 * @copyright Martin Kluska
 * @web http://imakers.cz
 * 
 */

class METnoPrecipitation implements JsonSerializable {
    protected $value    = 0;
    protected $min      = 0;
    protected $max      = 0;
    
    public function __construct($value,$min,$max) {
        $this->value    = $value;
        $this->min      = $min;
        $this->max      = $max;
    }
    
    public function __toString() {
        return "$this->value";
    }
    
    public function getValue() {
        return $this->value;
    }
    
    public function getMIN() {
        return $this->min;
    }
    
    public function getMAX() {
        return $this->max;
    }

    /**
     * @return mixed
     */
    function jsonSerialize()
    {
        return array(
            "value" => $this->getValue(),
            "min" => $this->getMIN(),
            "max" => $this->getMAX()
        );
    }


}