<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

trait ConfigTestTrait
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /**
     * @param mixed $expects
     * @param string $key
     * @param mixed $value
     */
    protected function setConfig($expects, $key, $value)
    {
        if (null === $this->configManager) {
            throw new \RuntimeException('ConfigManager is not initialized');
        }

        $this->configManager->expects($expects)
            ->method('get')
            ->with($this->getConfigKey($key))
            ->willReturn($value);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getConfigKey($key)
    {
        return OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
