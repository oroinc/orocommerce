<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Symfony\Component\HttpFoundation\ParameterBag;

class MoneyOrderConfigTest extends \PHPUnit_Framework_TestCase
{
    const LABEL = 'label';
    const SHORT_LABEL = 'short label';
    const PAY_TO = 'pay to';
    const SEND_TO = 'send to';
    const CHANNEL_ID = 5;

    /** @var MoneyOrderConfig */
    private $config;

    /** @var Channel|\PHPUnit_Framework_MockObject_MockObject */
    private $channel;

    protected function setUp()
    {
        $settingsBag = new ParameterBag([
            'money_order_label' => self::LABEL,
            'money_order_short_label' => self::SHORT_LABEL,
            'money_order_pay_to' => self::PAY_TO,
            'money_order_send_to' => self::SEND_TO,
        ]);

        $transport = $this->createMock(Transport::class);
        $transport->expects(static::any())
            ->method('getSettingsBag')
            ->willReturn($settingsBag);

        $this->channel = $this->createMock(Channel::class);
        $this->channel->expects(static::any())
            ->method('getTransport')
            ->willReturn($transport);

        $this->config = new MoneyOrderConfig($this->channel);
    }

    public function testGetLabelReturnsCorrectString()
    {
        static::assertSame(self::LABEL, $this->config->getLabel());
    }

    public function testGetShortLabelReturnsCorrectString()
    {
        static::assertSame(self::SHORT_LABEL, $this->config->getShortLabel());
    }

    public function testGetAdminLabelReturnsCorrectString()
    {
        static::assertSame(self::LABEL, $this->config->getAdminLabel());
    }

    public function testGetPayToReturnsCorrectString()
    {
        static::assertSame(self::PAY_TO, $this->config->getPayTo());
    }

    public function testGetSendToReturnsCorrectString()
    {
        static::assertSame(self::SEND_TO, $this->config->getSendTo());
    }

    public function testGetPaymentMethodIdentifierReturnsCorrectString()
    {
        $this->channel->expects(static::once())
            ->method('getId')
            ->willReturn(self::CHANNEL_ID);

        static::assertSame(
            'money_order_' . self::CHANNEL_ID,
            $this->config->getPaymentMethodIdentifier()
        );
    }
}
