#Expand Units of Length, Units of Weight and Freight Classes#

To expand "units" and/or "freight classes", first of all must be implemented migration ([example](#example-migration)), that contain all expected "units" and/or "freight classes" definitions.
The migration must extend "LoadUnitsAndFreightClassesData" and should have the following properties regarding required items.

 * $weightUnits -- for Weight Units
 * $lengthUnits -- for Length Units
 * $freightClasses -- for Freight Classes
 
At next step, translations should be added for all new "units" and/or "freight classes", at least, translations for default locale should be added. ([example](#example-translations))

After all above is done, "migration update script" must be executed to register new "units" and/or "freight classes" within application.
```bash
app/console cache:clear
app/console oro:migration:data:load
```
At final step, all new "units" and/or "freight classes" should be activated at system configuration

```code
System -> Configuration -> Commerce -> Shipping -> Shipping Options
```

#### Example migration

```php
<?php

namespace OroB2B\Bundle\ShippingDemoBundle\Migrations\Data\ORM;

use OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM\LoadUnitsAndFreightClassesData as BaseLoadUnitData;

class LoadUnitsAndFreightClassesData extends BaseLoadUnitData
{
    /** @var array */
    protected $weightUnits = [
        ['code' => 'demo_lbs', 'conversion_rates' => []],
    ];

    /** @var array */
    protected $lengthUnits = [
        ['code' => 'demo_cm', 'conversion_rates' => []],
    ];

    /** @var array */
    protected $freightClasses = [
        ['code' => 'demo_parcel'],
        ['code' => 'demo_class50'],
        ['code' => 'demo_class55'],
    ];
}
```

#### Example translations
```yml
orob2b:
    weight_unit.demo_lbs:
        label:
            full: demo_pound
            full_plural: demo_pounds
            short: dlbs
            short_plural: dlbs
        value:
            full: '{0} none|{1} %count% pound|]1,Inf] %count% demo_pounds'
            short: '{0} none|{1} %count% demo_lbs|]1,Inf] %count% demo_lbs'

    length_unit.demo_cm:
        label:
            full: demo_centimeter
            full_plural: demo_centimeters
            short: dcm
            short_plural: dcm

    freight_class.demo_parcel:
        label:
            full: demo_parcel
            full_plural: demo_parcels
            short: dpel
            short_plural: dpels

    ... More Translations ...
```
