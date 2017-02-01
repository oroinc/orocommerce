<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCheckoutExtensionTest extends ExtensionTestCase
{
    /** @var OroCheckoutExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function buildContainerMock()
    {
        $mockBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig', 'getParameter'])
            ->getMock();

        $mockBuilder
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn(['OroSaleBundle' => []]);

        return $mockBuilder;
    }

    protected function setUp()
    {
        $this->extension = new OroCheckoutExtension();
    }

    protected function tearDown()
    {
        unset($this->extension);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_checkout.layout.data_provider.shipping_context',
            'oro_checkout.shipping_method.provider_main',
            'oro_checkout.shipping_method.quote_provider_chain_element',
            'oro_checkout.shipping_method.price_provider_chain_element',
            'oro_checkout.condition.checkout_has_applicable_shipping_methods',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroCheckoutExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroCheckoutExtension::ALIAS, $this->extension->getAlias());
    }
}
