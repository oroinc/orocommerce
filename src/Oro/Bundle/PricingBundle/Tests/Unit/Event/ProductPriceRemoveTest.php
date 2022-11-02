<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;

class ProductPriceRemoveTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPrice(): void
    {
        $price = new ProductPrice();

        $event = new ProductPriceRemove($price);

        $this->assertSame($price, $event->getPrice());
    }

    public function testSetAndGetEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManager::class);

        $event = new ProductPriceRemove(new ProductPrice());

        $this->assertNull($event->getEntityManager());

        $event->setEntityManager($entityManager);

        $this->assertSame($entityManager, $event->getEntityManager());
    }
}
