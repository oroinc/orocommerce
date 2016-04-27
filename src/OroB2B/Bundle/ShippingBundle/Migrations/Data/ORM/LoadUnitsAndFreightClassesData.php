<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

class LoadUnitsAndFreightClassesData extends AbstractUnitsAndFreightClassesFixture
{
    /** @var array */
    protected $weightUnits = [
        ['code' => 'lbs', 'conversion_rates' => []],
        ['code' => 'kg', 'conversion_rates' => []]
    ];

    /** @var array */
    protected $dimensionUnits = [
        ['code' => 'inch', 'conversion_rates' => []],
        ['code' => 'ft', 'conversion_rates' => []],
        ['code' => 'cm', 'conversion_rates' => []],
        ['code' => 'm', 'conversion_rates' => []]
    ];

    /** @var array */
    protected $freightClasses = [
        ['code' => 'pel'] // parcel
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addWeightUnits($manager, $this->weightUnits);
        $this->addDimensionUnits($manager, $this->dimensionUnits);
        $this->addFreightClasses($manager, $this->freightClasses);

        $manager->flush();
    }
}
