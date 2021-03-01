<?php
declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCheckoutExtensionTest extends ExtensionTestCase
{
    protected function buildContainerMock(): ContainerBuilder
    {
        $containerBuilder = parent::buildContainerMock();
        $containerBuilder
            ->expects(static::once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn(['OroSaleBundle' => []]);

        return $containerBuilder;
    }

    public function testLoad(): void
    {
        $this->loadExtension(new OroCheckoutExtension());

        $expectedDefinitions = [
            'oro_checkout.provider.shipping_context',
            'oro_checkout.shipping_method.provider_main',
            'oro_checkout.shipping_method.quote_provider_chain_element',
            'oro_checkout.shipping_method.price_provider_chain_element',
            'oro_checkout.condition.checkout_has_applicable_shipping_methods',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
        $this->assertExtensionConfigsLoaded([OroCheckoutExtension::ALIAS]);
    }

    public function testGetAlias(): void
    {
        static::assertEquals(OroCheckoutExtension::ALIAS, (new OroCheckoutExtension())->getAlias());
    }
}
