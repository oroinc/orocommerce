<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroPromotionExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroPromotionExtension());

        $expectedDefinitions = [
            'oro_promotion.rule_filtration.service',
            'oro_promotion.rule_filtration.scope_decorator',
            'oro_promotion.rule_filtration.schedule_decorator',
            'oro_promotion.rule_filtration.matching_items',
            'oro_promotion.promotion.context_data_converter_registry',
            'oro_promotion.promotion_provider',
            'oro_promotion.discount_factory',
            'oro_promotion.discount_type_to_form_type_provider',
            'oro_promotion.discount.shipping_discount',
            'oro_promotion.discount.order_discount',
            'oro_promotion.discount.buy_x_get_y_discount',
            'oro_promotion.importexport.configuration_provider.coupon',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $sharedFalseDefinitions = [
            'oro_promotion.discount.shipping_discount',
            'oro_promotion.discount.order_discount',
            'oro_promotion.discount.buy_x_get_y_discount'
        ];
        foreach ($sharedFalseDefinitions as $sharedFalseDefinition) {
            $this->assertFalse($this->actualDefinitions[$sharedFalseDefinition]->isShared());
        }

        $expectedExtensionConfigs = [
            'oro_promotion',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroPromotionExtension();
        $this->assertEquals('oro_promotion', $extension->getAlias());
    }
}
