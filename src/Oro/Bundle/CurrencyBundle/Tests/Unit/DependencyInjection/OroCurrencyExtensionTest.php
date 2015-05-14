<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\DependencyInjection\OroCurrencyExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCurrencyExtensionTest extends ExtensionTestCase
{
    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroCurrencyExtension();

        $this->assertEquals('oro_currency', $extension->getAlias());
    }
}
