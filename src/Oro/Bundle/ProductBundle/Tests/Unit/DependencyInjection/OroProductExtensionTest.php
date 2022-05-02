<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ProductBundle\Controller\Api\Rest\BrandController;
use Oro\Bundle\ProductBundle\Controller\Api\Rest\InlineEditProductController;
use Oro\Bundle\ProductBundle\DependencyInjection\OroProductExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroProductExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroProductExtension());

        $expectedDefinitions = [
            'oro_product.form.type.product',
            'oro_product.form.type.product_step_one',
            'oro_product.provider.default_product_unit_provider.chain',
            'oro_product.provider.default_product_unit_provider.system',
            'oro_product.service.single_unit_mode',
            'oro_product.virtual_fields.decorator_factory',
            'oro_product.virtual_fields.select_query_converter',
            'oro_product.importexport.configuration_provider.product',
            BrandController::class,
            InlineEditProductController::class,
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = [
            'oro_product',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }
}
