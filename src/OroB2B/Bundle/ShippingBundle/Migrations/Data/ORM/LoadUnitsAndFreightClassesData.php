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
    protected $lengthUnits = [
        ['code' => 'inch', 'conversion_rates' => []],
        ['code' => 'foot', 'conversion_rates' => []],
        ['code' => 'cm', 'conversion_rates' => []],
        ['code' => 'm', 'conversion_rates' => []]
    ];

    /** @var array */
    protected $freightClasses = [
        ['code' => 'parcel']
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addWeightUnits($manager, $this->weightUnits);
        $this->addLengthUnits($manager, $this->lengthUnits);
        $this->addFreightClasses($manager, $this->freightClasses);

        $manager->flush();
    }
}
