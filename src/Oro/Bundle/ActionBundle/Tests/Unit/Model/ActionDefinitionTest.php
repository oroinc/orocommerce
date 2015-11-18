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
        static::assertPropertyAccessors(
            $this->definition,
            [
                ['name', 'test'],
                ['label', 'test'],
                ['enabled', false],
                ['entities', ['entity1', 'entity2']],
                ['routes', ['route1', 'route2']],
                ['applications', ['application1', 'application2']],
                ['order', 77],
                ['frontendOptions', ['config1', 'config2']],
                ['formOptions', ['config1', 'config2']],
                ['attributes', ['config1', 'config2']],
                ['preConditions', ['config1', 'config2']],
                ['conditions', ['config1', 'config2']],
                ['initStep', ['config1', 'config2']],
                ['executionStep', ['config1', 'config2']],
            ]
        );
    }
}
