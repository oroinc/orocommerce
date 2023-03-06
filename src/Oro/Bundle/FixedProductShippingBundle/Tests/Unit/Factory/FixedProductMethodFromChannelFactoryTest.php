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
    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierGenerator;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIconProvider;

    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    /** @var ShippingCostProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostProvider;

    /** @var FixedProductMethodFromChannelFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->integrationIconProvider = $this->createMock(IntegrationIconProviderInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->shippingCostProvider = $this->createMock(ShippingCostProvider::class);

        $this->factory = new FixedProductMethodFromChannelFactory(
            $this->identifierGenerator,
            $this->localizationHelper,
            $this->integrationIconProvider,
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    public function testCreate(): void
    {
        $identifier = 'fixed_product_1';
        $label = 'test';
        $iconUri = 'bundles/icon-uri.png';
        $enabled = true;

        $channel = new Channel();
        $channel->setTransport(new FixedProductSettings());
        $channel->setEnabled($enabled);

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

        $expected = new FixedProductMethod(
            $identifier,
            $label,
            $iconUri,
            $enabled,
            $this->roundingService,
            $this->shippingCostProvider
        );
        self::assertEquals($expected, $this->factory->create($channel));
    }
}
