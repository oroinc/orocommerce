<?php
namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractPaymentConfigTestCase extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;

    /** @var PaymentConfigInterface */
    protected $config;

    /** @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getPaymentConfig($this->configManager);
    }

    /**
     * @param ConfigManager $configManager
     * @return PaymentConfigInterface
     */
    abstract protected function getPaymentConfig(ConfigManager $configManager);

    /**
     * @return string
     */
    abstract protected function getConfigPrefix();

    public function testGetLabel()
    {
        $this->assertSame($this->getConfigPrefix() . 'label', $this->config->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->assertSame($this->getConfigPrefix() . 'short_label', $this->config->getShortLabel());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPaymentExtension::ALIAS;
    }
}
