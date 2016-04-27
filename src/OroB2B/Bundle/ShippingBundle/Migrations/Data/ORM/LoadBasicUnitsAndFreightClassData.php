<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM;

class LoadBasicUnitsAndFreightClassData extends AbstractBasicUnitsAndFreightClassFixture
{
    protected $weightUnits = [
        ['code' => 'lbs' , 'conversion_rates' => []],
        ['code' => 'kg' , 'conversion_rates' => []],
    ];
    protected $dimensionUnits = [
        ['code' => 'inches' , 'conversion_rates' => []],
        ['code' => 'feet & inches' , 'conversion_rates' => []],
        ['code' => 'cm' , 'conversion_rates' => []],
        ['code' => 'm' , 'conversion_rates' => []],
    ];
    protected $freightClasses = [
        ['code' => 'Parcel'],
    ];
}
