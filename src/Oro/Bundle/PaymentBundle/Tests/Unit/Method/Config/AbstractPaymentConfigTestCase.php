<?php
namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractPaymentConfigTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentConfigInterface */
    protected $config;

    protected function setUp(): void
    {
        $this->config = $this->getPaymentConfig();
    }

    abstract protected function getPaymentConfig(): PaymentConfigInterface;

    public function testGetLabel()
    {
        $this->assertSame('test label', $this->config->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->assertSame('test short label', $this->config->getShortLabel());
    }

    public function testGetAdminLabel()
    {
        $this->assertSame('test admin label', $this->config->getAdminLabel());
    }

    public function testGetPaymentMethodIdentifier()
    {
        $this->assertSame('test_payment_method_identifier', $this->config->getPaymentMethodIdentifier());
    }
}
