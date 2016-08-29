<?php
namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractPaymentConfigTestCase extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;

    /** @var PaymentConfigInterface */
    protected $config;

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

    public function testIsEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'enabled', $returnValue);
        $this->assertSame($returnValue, $this->config->isEnabled());
    }

    public function testGetLabel()
    {
        $returnValue = 'test label';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'label', $returnValue);
        $this->assertSame($returnValue, $this->config->getLabel());
    }

    public function testGetShortLabel()
    {
        $returnValue = 'test short label';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'short_label', $returnValue);
        $this->assertSame($returnValue, $this->config->getShortLabel());
    }

    public function testGetOrder()
    {
        $returnValue = 12;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'sort_order', $returnValue);
        $this->assertSame($returnValue, $this->config->getOrder());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPaymentExtension::ALIAS;
    }
}
