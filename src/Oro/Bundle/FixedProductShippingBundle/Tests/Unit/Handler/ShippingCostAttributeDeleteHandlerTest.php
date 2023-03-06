<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FixedProductShippingBundle\Handler\ShippingCostAttributeDeleteHandler;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelLoaderInterface;
use Oro\Component\Testing\ReflectionUtil;

class ShippingCostAttributeDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ChannelLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $channelLoader;

    /** @var ShippingCostAttributeDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->channelLoader = $this->createMock(ChannelLoaderInterface::class);

        $this->handler = new ShippingCostAttributeDeleteHandler($this->doctrine, $this->channelLoader);
    }

    private function getChannel(int $id, bool $enabled): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);
        $channel->setType(FixedProductChannelType::TYPE);
        $channel->setEnabled($enabled);

        return $channel;
    }

    private function getAttribute(string $fieldName): PriceAttributePriceList
    {
        $attribute = new PriceAttributePriceList();
        $attribute->setFieldName($fieldName);
        $attribute->setOrganization($this->createMock(Organization::class));

        return $attribute;
    }

    public function testIsAttributeFixedForNotShippingCostAttribute(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertFalse($this->handler->isAttributeFixed($this->getAttribute('notShippingCost')));
    }

    public function testIsAttributeFixedWhenNoIntegrations(): void
    {
        $attribute = $this->getAttribute('shippingCost');

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with(FixedProductChannelType::TYPE, self::isFalse(), self::identicalTo($attribute->getOrganization()))
            ->willReturn([]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertFalse($this->handler->isAttributeFixed($attribute));
    }

    public function testIsAttributeFixedWhenHasEnabledIntegrations(): void
    {
        $attribute = $this->getAttribute('shippingCost');
        $channel = $this->getChannel(1, true);

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with(FixedProductChannelType::TYPE, self::isFalse(), self::identicalTo($attribute->getOrganization()))
            ->willReturn([$channel]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertTrue($this->handler->isAttributeFixed($attribute));
    }

    public function testIsAttributeFixedWhenNoEnabledIntegrationAndIntegrationMentionedInShippingRules(): void
    {
        $attribute = $this->getAttribute('shippingCost');
        $channel = $this->getChannel(1, false);

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with(FixedProductChannelType::TYPE, self::isFalse(), self::identicalTo($attribute->getOrganization()))
            ->willReturn([$channel]);

        $shippingMethodConfigRepository = $this->createMock(ShippingMethodConfigRepository::class);
        $shippingMethodConfigRepository->expects(self::once())
            ->method('configExistsByMethods')
            ->with([sprintf('%s_%s', FixedProductChannelType::TYPE, 1)])
            ->willReturn(true);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ShippingMethodConfig::class)
            ->willReturn($shippingMethodConfigRepository);

        self::assertTrue($this->handler->isAttributeFixed($attribute));
    }

    public function testIsAttributeFixedWhenNoEnabledIntegrationAndIntegrationNotMentionedInShippingRules(): void
    {
        $attribute = $this->getAttribute('shippingCost');
        $channel = $this->getChannel(1, false);

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with(FixedProductChannelType::TYPE, self::isFalse(), self::identicalTo($attribute->getOrganization()))
            ->willReturn([$channel]);

        $shippingMethodConfigRepository = $this->createMock(ShippingMethodConfigRepository::class);
        $shippingMethodConfigRepository->expects(self::once())
            ->method('configExistsByMethods')
            ->with([sprintf('%s_%s', FixedProductChannelType::TYPE, 1)])
            ->willReturn(false);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ShippingMethodConfig::class)
            ->willReturn($shippingMethodConfigRepository);

        self::assertFalse($this->handler->isAttributeFixed($attribute));
    }
}
