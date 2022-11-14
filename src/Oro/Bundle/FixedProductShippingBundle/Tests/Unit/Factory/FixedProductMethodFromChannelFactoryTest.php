<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\FixedProductShippingBundle\Factory\FixedProductMethodFromChannelFactory;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethod;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class FixedProductMethodFromChannelFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIconProvider;

    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierGenerator;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var FixedProductMethodFromChannelFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->integrationIconProvider = $this->createMock(IntegrationIconProviderInterface::class);

        $this->factory = new FixedProductMethodFromChannelFactory(
            $this->identifierGenerator,
            $this->localizationHelper,
            $this->integrationIconProvider,
            $this->createMock(RoundingServiceInterface::class),
            $this->createMock(ShippingCostProvider::class)
        );
    }

    public function testBuildReturnsCorrectObjectWithLabel(): void
    {
        $label = 'test';
        $channel = $this->getChannel();
        $identifier = 'fixed_product_1';
        $iconUri = 'bundles/icon-uri.png';

        $this->integrationIconProvider->expects($this->once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn($iconUri);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn($label);

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $method = $this->factory->create($channel);

        $this->assertInstanceOf(FixedProductMethod::class, $method);
        $this->assertSame($identifier, $method->getIdentifier());
        $this->assertSame($label, $method->getLabel());
        $this->assertTrue($method->isEnabled());
        $this->assertSame($iconUri, $method->getIcon());
    }

    private function getChannel(): Channel
    {
        $channel = new Channel();
        $channel->setTransport(new FixedProductSettings());
        $channel->setEnabled(true);

        return $channel;
    }
}
