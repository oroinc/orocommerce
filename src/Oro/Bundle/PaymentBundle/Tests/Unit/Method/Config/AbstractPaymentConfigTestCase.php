<?php
namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractPaymentConfigTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentConfigInterface
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->config = $this->getPaymentConfig();
    }

    /**
     * @return PaymentConfigInterface
     */
    abstract protected function getPaymentConfig();

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
