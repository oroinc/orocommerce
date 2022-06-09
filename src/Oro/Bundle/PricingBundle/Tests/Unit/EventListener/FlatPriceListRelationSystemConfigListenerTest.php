<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\FlatPriceListRelationSystemConfigListener;
use Oro\Bundle\PricingBundle\Handler\PriceListRelationHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class FlatPriceListRelationSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FlatPriceListRelationSystemConfigListener */
    private $listener;

    /** @var PriceListRelationHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListRelationHandler;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->priceListRelationHandler = $this->createMock(PriceListRelationHandler::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new FlatPriceListRelationSystemConfigListener(
            $this->priceListRelationHandler,
            $this->producer,
            $this->doctrine
        );
        $this->assertFeatureChecker();
    }

    public function testBeforeSave(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->doctrine
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

        $this->priceListRelationHandler
            ->expects($this->once())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveFlatPriceTopic::getName(), ['priceList' => $priceList->getId()]);

        $configManager = $this->createMock(ConfigManager::class);
        $event = new ConfigSettingsUpdateEvent($configManager, ['value' => 1]);
        $this->listener->beforeSave($event);
    }

    /**
     * @dataProvider listenerDisabledDataProvider
     *
     * @param bool $listenerEnabled
     * @param bool $featureEnabled
     *
     * @return void
     */
    public function testListenerDisabled(bool $listenerEnabled, bool $featureEnabled): void
    {
        $this->assertListenerStatus($listenerEnabled, $featureEnabled);
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $this->priceListRelationHandler
            ->expects($this->any())
            ->method('isPriceListAlreadyUsed')
            ->with($priceList)
            ->willReturn(false);

        $this->producer
            ->expects($this->never())
            ->method('send');

        $configManager = $this->createMock(ConfigManager::class);
        $event = new ConfigSettingsUpdateEvent($configManager, ['value' => 1]);
        $this->listener->beforeSave($event);
    }

    public function listenerDisabledDataProvider(): array
    {
        return [
            'Listener disabled and feature disabled' => [false, false],
            'Listener disabled' => [false, true],
            'Feature disabled' => [true, false],
        ];
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
