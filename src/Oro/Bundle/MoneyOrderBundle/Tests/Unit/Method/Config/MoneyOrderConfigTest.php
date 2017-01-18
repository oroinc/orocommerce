<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;

class MoneyOrderConfigTest extends \PHPUnit_Framework_TestCase
{
    const LABEL = 'label';
    const SHORT_LABEL = 'short label';
    const ADMIN_LABEL = 'admin label';
    const PAY_TO = 'pay to';
    const SEND_TO = 'send to';
    const IDENTIFIER = 'id';

    /** @var MoneyOrderConfig */
    private $config;

    protected function setUp()
    {
        $this->config = new MoneyOrderConfig(
            self::LABEL,
            self::SHORT_LABEL,
            self::ADMIN_LABEL,
            self::PAY_TO,
            self::SEND_TO,
            self::IDENTIFIER
        );
    }

    public function testGetters()
    {
        static::assertSame(self::LABEL, $this->config->getLabel());
        static::assertSame(self::SHORT_LABEL, $this->config->getShortLabel());
        static::assertSame(self::ADMIN_LABEL, $this->config->getAdminLabel());
        static::assertSame(self::PAY_TO, $this->config->getPayTo());
        static::assertSame(self::SEND_TO, $this->config->getSendTo());
        static::assertSame(self::IDENTIFIER, $this->config->getPaymentMethodIdentifier());
    }
}
