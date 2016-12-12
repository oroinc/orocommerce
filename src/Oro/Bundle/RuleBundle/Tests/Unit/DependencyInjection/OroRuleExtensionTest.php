<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RuleBundle\DependencyInjection\OroRuleExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroRuleExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroRuleExtension());
    }
}
