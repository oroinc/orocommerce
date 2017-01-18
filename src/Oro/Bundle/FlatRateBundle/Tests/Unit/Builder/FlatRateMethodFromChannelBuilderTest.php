<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Builder;

use Oro\Bundle\FlatRateBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\FlatRateBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class FlatRateMethodFromChannelBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var IntegrationMethodIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $identifierGenerator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LocalizationHelper */
    private $localizationHelper;

    /** @var FlatRateMethodFromChannelBuilder */
    private $builder;

    protected function setUp()
    {
        $this->identifierGenerator = $this->createMock(IntegrationMethodIdentifierGeneratorInterface::class);

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new FlatRateMethodFromChannelBuilder($this->identifierGenerator, $this->localizationHelper);
    }

    public function testBuildReturnsCorrectObjectWithLabel()
    {
        $label = 'test';
        $channel = $this->getChannel();
        $identifier = 'flat_rate_1';

        $this->localizationHelper->expects(static::once())
            ->method('getLocalizedValue')
            ->willReturn($label);

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $method = $this->builder->build($channel);

        static::assertInstanceOf(FlatRateMethod::class, $method);
        static::assertSame($identifier, $method->getIdentifier());
        static::assertSame($label, $method->getLabel());
    }

    /**
     * @return Channel
     */
    private function getChannel()
    {
        $settings = new FlatRateSettings();

        $channel = new Channel();
        $channel->setTransport($settings);

        return $channel;
    }
}
