<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\ShoppingListBundle\DependencyInjection\OroB2BShoppingListExtension;

class OroB2BShoppingListExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroB2BShoppingListExtension());

        $expectedParameters = [
            'orob2b_shopping_list.entity.shopping_list.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildContainerMock()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter'])
            ->getMock();
    }
}
