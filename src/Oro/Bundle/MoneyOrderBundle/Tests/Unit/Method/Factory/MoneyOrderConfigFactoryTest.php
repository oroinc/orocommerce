<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderConfigFactory;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class MoneyOrderConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $localizationHelper;

    /** @var IntegrationMethodIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $identifierGenerator;

    /** @var MoneyOrderConfigFactory */
    private $factory;

    protected function setUp()
    {
        $this->localizationHelper =$this->createMock(LocalizationHelper::class);
        $this->identifierGenerator = $this->createMock(
            IntegrationMethodIdentifierGeneratorInterface::class
        );

        $this->factory = new MoneyOrderConfigFactory(
            $this->localizationHelper,
            $this->identifierGenerator
        );
    }

    public function testCreate()
    {
        $label = 'label';
        $payTo = 'pay to';
        $sendTo = 'send to';
        $identifier = 'id';

        $settings = new MoneyOrderSettings();
        $settings->setPayTo($payTo)
            ->setSendTo($sendTo)
        ;

        $this->localizationHelper->expects(static::any())
            ->method('getLocalizedValue')
            ->willReturn($label);

        $this->identifierGenerator->expects(static::any())
            ->method('generateIdentifier')
            ->willReturn($identifier);

        $channel = new Channel();
        $channel->setTransport($settings);

        $expected = new MoneyOrderConfig(
            $label,
            $label,
            $label,
            $payTo,
            $sendTo,
            $identifier
        );

        static::assertEquals($expected, $this->factory->create($channel));
    }
}
