<?php
namespace OroB2B\Bundle\OrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\OrderBundle\DependencyInjection\OroB2BOrderExtension;

class OroB2BOrderExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroB2BOrderExtension());

        $expectedParameters = [
            'orob2b_order.order.entity.class',
            'orob2b_order.order.manager.api.class'

        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_order.form.type.order',
            'orob2b_order.order.manager.api'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
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
