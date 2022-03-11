<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPricesListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPricesListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductPricesListener */
    private $productPricesListener;

    /** @var CombinedPriceListBuildTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $combinedPriceListBuildTriggerHandler;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    protected function setUp(): void
    {
        $this->combinedPriceListBuildTriggerHandler = $this->createMock(CombinedPriceListBuildTriggerHandler::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);

        $this->productPricesListener = new ProductPricesListener(
            $this->combinedPriceListBuildTriggerHandler,
            $this->priceListTriggerHandler
        );
    }

    public function testIsDisabled(): void
    {
        $this->productPricesListener->setEnabled(false);

        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->never())
            ->method('handle');

        $entityManager = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdated($entityManager, [], [], [], []);
        $this->productPricesListener->onPricesUpdated($event);
    }

    public function testOnPricesUpdated(): void
    {
        $priceListToSaveUpdate = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListToToRemove = $this->getEntity(PriceList::class, ['id' => 2]);

        $productPriceToSave = $this->getEntity(ProductPrice::class, ['priceList' => $priceListToSaveUpdate]);
        $productPriceToUpdate = $this->getEntity(ProductPrice::class, ['priceList' => $priceListToSaveUpdate]);
        $productPriceToRemove = $this->getEntity(ProductPrice::class, ['priceList' => $priceListToToRemove]);

        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->exactly(2))
            ->method('handle')
            ->withConsecutive(
                [$priceListToSaveUpdate],
                [$priceListToToRemove]
            );

        $entityManager = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdated(
            $entityManager,
            [$productPriceToRemove],
            [$productPriceToSave],
            [$productPriceToUpdate],
            []
        );
        $this->productPricesListener->onPricesUpdated($event);
    }
}
