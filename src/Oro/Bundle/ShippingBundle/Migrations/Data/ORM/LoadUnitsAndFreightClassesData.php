<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads product units and freight classes.
 */
class LoadUnitsAndFreightClassesData extends AbstractUnitsAndFreightClassesFixture implements VersionedFixtureInterface
{
    private array $weightUnits = [
        ['code' => 'lbs', 'conversion_rates' => [
            'kg' => 0.45359237
        ]],
        ['code' => 'kg', 'conversion_rates' => [
            'lbs' => 2.20462262
        ]]
    ];

    private array $lengthUnits = [
        ['code' => 'inch', 'conversion_rates' => [
            'foot' => 0.0833333,
            'cm'   => 2.54,
            'm'    => 0.0254
        ]],
        ['code' => 'foot', 'conversion_rates' => [
            'inch' => 12,
            'cm'   => 30.48,
            'm'    => 0.3048
        ]],
        ['code' => 'cm', 'conversion_rates' => [
            'inch' => 0.393701,
            'foot' => 0.0328084,
            'm'    => 0.01
        ]],
        ['code' => 'm', 'conversion_rates' => [
            'inch' => 39.3701,
            'foot' => 3.28084,
            'cm'   => 100
        ]]
    ];

    private array $freightClasses = [
        ['code' => 'parcel']
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->addUpdateWeightUnits($manager, $this->weightUnits);
        $this->addUpdateLengthUnits($manager, $this->lengthUnits);
        $this->addUpdateFreightClasses($manager, $this->freightClasses);

        $manager->flush();
    }

    #[\Override]
    public function getVersion(): string
    {
        return '1.1';
    }
}
