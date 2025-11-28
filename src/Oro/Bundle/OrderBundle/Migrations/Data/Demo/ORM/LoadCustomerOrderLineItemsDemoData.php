<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;

/**
 * Adding line items to orders has been moved to {@see LoadCustomerOrderDemoData::load()}
 * This has been a no-op since 5.1 LTS (BAP-21515) and remains available in 6.1 solely for backward compatibility.
 * Will be removed in 7.0.
 */
class LoadCustomerOrderLineItemsDemoData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCustomerOrderDemoData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // do nothing
    }
}
