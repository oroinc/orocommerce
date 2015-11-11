<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionDefinition;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ActionDefinitionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ActionDefinition */
    protected $definition;

    protected function setUp()
    {
        $this->definition = new ActionDefinition();
    }

    /**
     * @dataProvider defaultsDataProvider
     *
     * @param string $method
     * @param mixed $value
     */
    public function testDefaults($method, $value)
    {
        static::assertEquals($value, $this->definition->$method());
    }

    /**
     * @return array
     */
    public function defaultsDataProvider()
    {
        return [
            [
                'method' => 'isEnabled',
                'value' => true
            ],
            [
                'method' => 'getEntities',
                'value' => []
            ],
            [
                'method' => 'getRoutes',
                'value' => []
            ],
            [
                'method' => 'getApplications',
                'value' => []
            ],
            [
                'method' => 'getOrder',
                'value' => 0
            ],
        ];
    }

    public function testGettersAndSetters()
    {
        $this->assertPropertyAccessors(
            $this->definition,
            [
                ['name', 'test'],
                ['label', 'test'],
                ['enabled', false],
                ['entities', ['entity1', 'entity2']],
                ['routes', ['route1', 'route2']],
                ['applications', ['application1', 'application2']],
                ['order', 77],
                ['frontendOptionsConfiguration', ['config1', 'config2']],
                ['formOptionsConfiguration', ['config1', 'config2']],
                ['attributesConfiguration', ['config1', 'config2']],
                ['preConditionsConfiguration', ['config1', 'config2']],
                ['conditionsConfiguration', ['config1', 'config2']],
                ['initStepConfiguration', ['config1', 'config2']],
                ['executionStepConfiguration', ['config1', 'config2']],
            ]
        );
    }
}
