<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Builder;

use Oro\Bundle\FlatRateBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\FlatRateBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class FlatRateMethodFromChannelBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|LocalizationHelper */
    private $localizationHelper;

    /** @var FlatRateMethodFromChannelBuilder */
    private $builder;

    protected function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new FlatRateMethodFromChannelBuilder($this->localizationHelper);
    }

    public function testBuildReturnsCorrectObjectWithLabel()
    {
        $label = 'test';
        $channel = $this->getChannel();

        $this->localizationHelper->expects(static::once())
            ->method('getLocalizedValue')
            ->willReturn($label);

        $method = $this->builder->build($channel);

        static::assertInstanceOf(FlatRateMethod::class, $method);
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
