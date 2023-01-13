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
    /** @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIconProvider;

    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierGenerator;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var FlatRateMethodFromChannelFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
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

        $this->integrationIconProvider->expects(self::once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn($iconUri);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->willReturn($label);

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $method = $this->factory->create($channel);

        self::assertInstanceOf(FlatRateMethod::class, $method);
        self::assertSame($identifier, $method->getIdentifier());
        self::assertSame($label, $method->getLabel());
        self::assertTrue($method->isEnabled());
        self::assertSame($iconUri, $method->getIcon());
    }

    private function getChannel(): Channel
    {
        $channel = new Channel();
        $channel->setTransport(new FlatRateSettings());
        $channel->setEnabled(true);

        return $channel;
    }
}
