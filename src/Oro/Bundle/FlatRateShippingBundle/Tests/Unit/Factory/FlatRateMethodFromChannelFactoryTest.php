<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class FlatRateMethodFromChannelFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationIconProvider;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $identifierGenerator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var FlatRateMethodFromChannelFactory
     */
    private $factory;

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

        $this->factory = new FlatRateMethodFromChannelFactory(
            $this->identifierGenerator,
            $this->localizationHelper,
            $this->integrationIconProvider
        );
    }

    public function testBuildReturnsCorrectObjectWithLabel()
    {
        $label = 'test';
        $channel = $this->getChannel();
        $identifier = 'flat_rate_1';
        $iconUri = 'bundles/icon-uri.png';

        $this->integrationIconProvider
            ->expects(static::once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn($iconUri);

        $this->localizationHelper->expects(static::once())
            ->method('getLocalizedValue')
            ->willReturn($label);

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $method = $this->factory->create($channel);

        static::assertInstanceOf(FlatRateMethod::class, $method);
        static::assertSame($identifier, $method->getIdentifier());
        static::assertSame($label, $method->getLabel());
        static::assertTrue($method->isEnabled());
        static::assertSame($iconUri, $method->getIcon());
    }

    /**
     * @return Channel
     */
    private function getChannel()
    {
        $settings = new FlatRateSettings();

        $channel = new Channel();
        $channel->setTransport($settings);
        $channel->setEnabled(true);

        return $channel;
    }
}
