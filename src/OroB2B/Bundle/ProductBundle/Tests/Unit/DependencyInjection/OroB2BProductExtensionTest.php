<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\ProductBundle\DependencyInjection\OroB2BProductExtension;

class OroB2BProductExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroB2BProductExtension());

        $expectedParameters = [
            'orob2b_product.product.class',
            'orob2b_product.form.type.product.class',
            'orob2b_product.form.type.product_unit_rounding_type.class',
            'orob2b_product.product.manager.api.class',
            'orob2b_product.service.rounding.class',
            'orob2b_product.form.type.product_default_visibility_type.class',

        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_product.form.type.product',
            'orob2b_product.form.type.product_unit_rounding_type',
            'orob2b_product.product.manager.api',
            'orob2b_product.service.rounding',
            'orob2b_product.form.type.default_visibility'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = [
            'orob2b_product',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BProductExtension();
        $this->assertEquals('orob2b_product', $extension->getAlias());
    }

    /**
     * {@inheritDoc}
     */
    protected function buildContainerMock()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig'])
            ->getMock();
    }

    /**
     * {@inheritDoc}
     */
    protected function getContainerMock()
    {
        $container = parent::getContainerMock();
        $container->expects($this->once())
            ->method('prependExtensionConfig')
            ->will(
                $this->returnCallback(
                    function ($name, array $config) {
                        if (!isset($this->extensionConfigs[$name])) {
                            $this->extensionConfigs[$name] = [];
                        }
                        array_unshift($this->extensionConfigs[$name], $config);
                    }
                )
            );
        return $container;
    }

    /**
     * @param array $expectedExtensionConfigs
     */
    protected function assertExtensionConfigsLoaded(array $expectedExtensionConfigs)
    {
        foreach ($expectedExtensionConfigs as $extensionName) {
            $this->assertArrayHasKey(
                $extensionName,
                $this->extensionConfigs,
                sprintf('Config for extension "%s" has not been loaded.', $extensionName)
            );
            $this->assertNotEmpty(
                $this->extensionConfigs[$extensionName],
                sprintf('Config for extension "%s" is empty.', $extensionName)
            );
        }
    }
}
