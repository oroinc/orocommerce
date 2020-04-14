<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RuleBundle\DependencyInjection\OroRuleExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroRuleExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroRuleExtension
     */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new OroRuleExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_rule.expression_language',
            'oro_rule.expression_language.function_count',
            'oro_rule.rule_filtration.service',
            'oro_rule.rule_filtration.enabled_decorator',
            'oro_rule.rule_filtration.stop_processing_decorator',
            'oro_rule.rule_filtration.expression_language_decorator',
            'oro_rule.action.visibility_provider',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroRuleExtension::ALIAS, $this->extension->getAlias());
    }
}
