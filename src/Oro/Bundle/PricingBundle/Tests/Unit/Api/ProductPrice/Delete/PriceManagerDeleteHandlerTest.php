<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Delete;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Delete\PriceManagerDeleteHandler;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use PHPUnit\Framework\TestCase;

class PriceManagerDeleteHandlerTest extends TestCase
{
    /**
     * @var PriceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceManager;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var PriceManagerDeleteHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->entityManager = $this->createMock(ObjectManager::class);

        $ownerDeletionManager = $this->createMock(OwnerDeletionManager::class);
        $ownerDeletionManager
            ->expects(static::any())
            ->method('hasAssignments')
            ->willReturn(false);

        $this->handler = new PriceManagerDeleteHandler($this->priceManager);
        $this->handler->setOwnerDeletionManager($ownerDeletionManager);
    }

    public function testProcessDeleteWrongType()
    {
        $this->priceManager
            ->expects(static::never())
            ->method('remove');

        $this->handler->processDelete(null, $this->entityManager);
    }

    public function testProcessDelete()
    {
        $productPrice = new ProductPrice();

        $this->priceManager
            ->expects(static::once())
            ->method('remove')
            ->with($productPrice);
        $this->priceManager
            ->expects(static::once())
            ->method('flush');

        $this->handler->processDelete($productPrice, $this->entityManager);
    }
}
