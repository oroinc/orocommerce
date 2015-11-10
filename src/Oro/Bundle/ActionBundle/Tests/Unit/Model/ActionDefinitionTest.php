<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionDefinition;

class ActionDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $actionDefinition = new ActionDefinition();
        static::assertEquals(ActionDefinition::EXTEND_STRATEGY_REPLACE, $actionDefinition->getExtendStrategy());
        static::assertEquals(0, $actionDefinition->getOrder());
        static::assertEquals(true, $actionDefinition->isEnabled());
    }

    public function testGetSetEnabled()
    {
        $actionDefinition = new ActionDefinition();
        static::assertInstanceOf(
            'Oro\Bundle\ActionBundle\Model\ActionDefinition',
            $actionDefinition->setEnabled(false)
        );
        static::assertEquals(false, $actionDefinition->isEnabled());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGettersAndSetters($property, $value)
    {
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);
        $actionDefinition = new ActionDefinition();
        static::assertInstanceOf(
            'Oro\Bundle\ActionBundle\Model\ActionDefinition',
            call_user_func_array([$actionDefinition, $setter], [$value])
        );
        static::assertEquals($value, call_user_func_array([$actionDefinition, $getter], []));
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            'name' => ['name', 'test'],
            'label' => ['label', 'test'],
            'entities' => ['entities', ['entity1', 'entity2']],
            'routes' => ['routes', ['route1', 'route2']],
            'applications' => ['applications', ['application1', 'application2']],
            'extend' => ['extend', 'test extend'],
            'extendStrategy' => ['extendStrategy', ActionDefinition::EXTEND_STRATEGY_ADD],
            'order' => ['order', 77],
            'frontendOptionsConfiguration' => ['frontendOptionsConfiguration', ['config1', 'config2']],
            'formOptionsConfiguration' => ['formOptionsConfiguration', ['config1', 'config2']],
            'attributesConfiguration' => ['attributesConfiguration', ['config1', 'config2']],
            'preConditionsConfiguration' => ['preConditionsConfiguration', ['config1', 'config2']],
            'conditionsConfiguration' => ['conditionsConfiguration', ['config1', 'config2']],
            'initStepConfiguration' => ['initStepConfiguration', ['config1', 'config2']],
            'executionStepConfiguration' => ['executionStepConfiguration', ['config1', 'config2']],
        ];
    }
}
