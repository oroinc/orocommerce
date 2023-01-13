<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FixedProductShippingBundle\Handler\ShippingCostAttributeDeleteHandler;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingCostAttributeDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry $managerRegistry*/
    private $managerRegistry;

    /** @var ShippingCostAttributeDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->handler = new ShippingCostAttributeDeleteHandler($this->managerRegistry);
    }

    public function testIsAttributeFixedWithAttribute(): void
    {
        $this->managerRegistry
            ->expects($this->never())
            ->method('getRepository');

        $attribute = $this->getEntity(PriceAttributePriceList::class, ['fieldName' => 'fieldName']);
        $this->assertFalse($this->handler->isAttributeFixed($attribute));
    }

    public function testIsAttributeFixedAndIntegrationEnabled(): void
    {
        $channel = new Channel();
        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository
            ->expects($this->once())
            ->method('findActiveByType')
            ->with(FixedProductChannelType::TYPE)
            ->willReturn([$channel]);

        $this->managerRegistry
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Channel::class, null, $channelRepository]
            ]);

        $attribute = $this->getEntity(PriceAttributePriceList::class, ['fieldName' => 'shippingCost']);
        $this->assertTrue($this->handler->isAttributeFixed($attribute));
    }

    public function testIsAttributeFixedAndIntegrationMentionedInShippingRules(): void
    {
        $channel = $this->getEntity(Channel::class, ['id' => 5, 'type' => FixedProductChannelType::TYPE]);
        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository
            ->expects($this->once())
            ->method('findByType')
            ->with(FixedProductChannelType::TYPE)
            ->willReturn([$channel]);

        $shippingMethodConfigRepository = $this->createMock(ShippingMethodConfigRepository::class);
        $shippingMethodConfigRepository
            ->expects($this->once())
            ->method('configExistsByMethods')
            ->with([sprintf('%s_%s', FixedProductChannelType::TYPE, 5)])
            ->willReturn(true);

        $this->managerRegistry
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Channel::class, null, $channelRepository],
                [ShippingMethodConfig::class, null, $shippingMethodConfigRepository],
            ]);

        $attribute = $this->getEntity(PriceAttributePriceList::class, ['fieldName' => 'shippingCost']);
        $this->assertTrue($this->handler->isAttributeFixed($attribute));
    }
}
