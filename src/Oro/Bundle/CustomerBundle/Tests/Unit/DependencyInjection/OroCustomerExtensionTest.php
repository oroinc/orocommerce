<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\CustomerBundle\DependencyInjection\OroCustomerExtension;

class OroCustomerExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroCustomerExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'oro_customer.entity.account.class',
            'oro_customer.entity.account_group.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_account', $extension->getAlias());
    }

    public function testPrepend()
    {
        $securityConfig = [
            0 => [
                'firewalls' => [
                    'frontend_secure' => ['frontend_secure_config'],
                    'frontend' => ['frontend_config'],
                    'main' => ['main_config'],
                ]
            ]
        ];
        $expectedSecurityConfig = [
            0 => [
                'firewalls' => [
                    'main' => ['main_config'],
                    'frontend_secure' => ['frontend_secure_config'],
                    'frontend' => ['frontend_config'],
                ]
            ]
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExtendedContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Oro\Component\DependencyInjection\ExtendedContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->exactly(2))
            ->method('getExtensionConfig')
            ->with('security')
            ->willReturnCallback(
                function () use (&$securityConfig) {
                    return $securityConfig;
                }
            );
        $containerBuilder->expects($this->exactly(2))
            ->method('setExtensionConfig')
            ->with('security', $this->isType('array'))
            ->willReturnCallback(
                function ($name, array $config = []) use (&$securityConfig) {
                    $securityConfig = $config;
                }
            );

        $extension = new OroCustomerExtension();
        $extension->prepend($containerBuilder);
        $this->assertEquals($expectedSecurityConfig, $securityConfig);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroCustomerExtension();

        $this->assertEquals(OroCustomerExtension::ALIAS, $extension->getAlias());
    }
}
