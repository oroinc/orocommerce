<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use OroB2B\Bundle\AccountBundle\DependencyInjection\OroB2BAccountExtension;

class OroB2BAccountExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroB2BAccountExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_account.entity.account.class',
            'orob2b_account.entity.account_group.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_account', $extension->getAlias());
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

        $extension = new OroB2BAccountExtension();
        $extension->prepend($containerBuilder);
        $this->assertEquals($expectedSecurityConfig, $securityConfig);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BAccountExtension();

        $this->assertEquals(OroB2BAccountExtension::ALIAS, $extension->getAlias());
    }
}
