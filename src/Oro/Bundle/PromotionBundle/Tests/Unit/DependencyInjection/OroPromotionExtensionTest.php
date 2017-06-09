<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;

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
            'oro_promotion.context_data_converter',
            'oro_promotion.promotion_provider',
            'oro_promotion.discount_factory',
            'oro_promotion.discount_type_to_form_type_provider'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

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
