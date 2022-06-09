<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\FlatPriceListRelationListener;
use Oro\Bundle\PricingBundle\Handler\PriceListRelationHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class FlatPriceListRelationListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FlatPriceListRelationListener */
    private $listener;

    /** @var PriceListRelationHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListRelationHandler;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->priceListRelationHandler = $this->createMock(PriceListRelationHandler::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new FlatPriceListRelationListener(
            $this->priceListRelationHandler,
            $this->producer,
            $this->configManager
        );
        $this->assertFeatureChecker();
    }

    public function testPreUpdate(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListRelation = $this->getEntity(PriceListToCustomer::class, ['id' => 1, 'priceList' => $priceList]);

        $this->assertListenerStatus();
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $this->priceListRelationHandler
            ->expects($this->once())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveFlatPriceTopic::getName(), ['priceList' => $priceList->getId()]);

        $this->listener->preUpdate($priceListRelation);
    }

    public function testPrePersist(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListRelation = $this->getEntity(PriceListToCustomer::class, ['id' => 1, 'priceList' => $priceList]);

        $this->assertListenerStatus();
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $this->priceListRelationHandler
            ->expects($this->once())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveFlatPriceTopic::getName(), ['priceList' => $priceList->getId()]);

        $this->listener->prePersist($priceListRelation);
    }

    /**
     * @dataProvider accuracyDataProvider
     *
     * @param string $relationClassName
     * @param string $accuracy
     * @param int $exactly
     *
     * @return void
     */
    public function testAccuracyPreUpdate(string $relationClassName, string $accuracy, int $exactly): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListRelation = $this->getEntity($relationClassName, ['id' => 1, 'priceList' => $priceList]);

        $this->assertListenerStatus();
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn($accuracy);

        $this->priceListRelationHandler
            ->expects($this->any())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->exactly($exactly))
            ->method('send');

        $this->listener->preUpdate($priceListRelation);
    }

    /**
     * @dataProvider accuracyDataProvider
     *
     * @param string $relationClassName
     * @param string $accuracy
     * @param int $exactly
     *
     * @return void
     */
    public function testAccuracyPrePersist(string $relationClassName, string $accuracy, int $exactly): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListRelation = $this->getEntity($relationClassName, ['id' => 1, 'priceList' => $priceList]);

        $this->assertListenerStatus();
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn($accuracy);

        $this->priceListRelationHandler
            ->expects($this->any())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->exactly($exactly))
            ->method('send');

        $this->listener->prePersist($priceListRelation);
    }

    private function accuracyDataProvider(): array
    {
        return [
            [PriceListToCustomer::class, 'customer', 1],
            [PriceListToCustomerGroup::class, 'customer', 1],
            [PriceListToCustomer::class, 'customer_group', 0],
            [PriceListToCustomerGroup::class, 'customer_group', 1],
            [PriceListToCustomer::class, 'website', 0],
            [PriceListToCustomerGroup::class, 'website', 0],
        ];
    }

    public function testIsFeatureDisabled(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListRelation = $this->getEntity(PriceListToCustomer::class, ['id' => 1, 'priceList' => $priceList]);

        $this->assertListenerStatus(false);
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $this->priceListRelationHandler
            ->expects($this->any())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->never())
            ->method('send');

        $this->listener->prePersist($priceListRelation);
    }

    private function assertFeatureChecker(): void
    {
        $this->featureChecker
            ->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnCallback(fn (string $feature) => $feature == 'oro_price_lists_flat');

        $this->listener->setFeatureChecker($this->featureChecker);
    }

    private function assertListenerStatus(bool $enabled = true, string $feature = 'oro_price_lists_flat'): void
    {
        $this->listener->setEnabled($enabled);
        $this->listener->addFeature($feature);
    }
}
