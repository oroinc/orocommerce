<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPricesUpdatedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testSetAndGetEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $priceListToSave = $this->getEntity(ProductPrice::class, ['id' => 1]);
        $priceListToUpdate = $this->getEntity(ProductPrice::class, ['id' => 2]);
        $priceListToRemove = $this->getEntity(ProductPrice::class, ['id' => 3]);

        $event = new ProductPricesUpdated(
            $entityManager,
            [$priceListToRemove],
            [$priceListToSave],
            [$priceListToUpdate],
            ['changeSet']
        );

        $this->assertSame($entityManager, $event->getEntityManager());
        $this->assertSame([$priceListToRemove], $event->getRemoved());
        $this->assertSame([$priceListToSave], $event->getSaved());
        $this->assertSame([$priceListToUpdate], $event->getUpdated());
    }
}
