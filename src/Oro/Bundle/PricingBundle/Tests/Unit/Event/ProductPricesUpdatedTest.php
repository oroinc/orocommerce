<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;

class ProductPricesUpdatedTest extends \PHPUnit\Framework\TestCase
{
    public function testSetAndGetEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManager::class);

        $event = new ProductPricesUpdated();

        $this->assertNull($event->getEntityManager());

        $event->setEntityManager($entityManager);

        $this->assertSame($entityManager, $event->getEntityManager());
    }
}
