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
use PHPUnit\Framework\TestCase;

class FixedProductMethodFromChannelFactoryTest extends TestCase
{
    /**
     * @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected IntegrationIconProviderInterface $integrationIconProvider;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected IntegrationIdentifierGeneratorInterface $identifierGenerator;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected LocalizationHelper $localizationHelper;

    /**
     * @var FixedProductMethodFromChannelFactory
     */
    protected FixedProductMethodFromChannelFactory $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->integrationIconProvider
            ->expects($this->once())
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
        $settings = new FixedProductSettings();

        $channel = new Channel();
        $channel->setTransport($settings);
        $channel->setEnabled(true);

        return $channel;
    }
}
