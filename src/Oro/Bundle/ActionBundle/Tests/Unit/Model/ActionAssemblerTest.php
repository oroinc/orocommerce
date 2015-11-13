<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionAssembler;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionAssembler
     */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new ActionAssembler($this->getConditionFactory());
    }

    protected function tearDown()
    {
        unset($this->assembler);
    }

    /**
     * @param array $configuration
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        static::assertEquals($expected, $definitions);
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException
     * @expectedExceptionMessage Option "label" is required
     */
    public function testAssembleWithMissedRequiredOptions()
    {
        $configuration = [
            'test_config' => [],
        ];

        $this->assembler->assemble($configuration);
    }

    /**
     * @return array
     */
    public function assembleProvider()
    {
        $definition1 = new ActionDefinition();
        $definition1
            ->setName('minimum_name')
            ->setLabel('My Label')
            ->setEntities(['My\Entity']);

        $definition2 = new ActionDefinition();
        $definition2
            ->setName('maximum_name')
            ->setLabel('My Label')
            ->setEntities(['My\Entity'])
            ->setRoutes(['my_route'])
            ->setEnabled(false)
            ->setApplications(['application1'])
            ->setAttributes(['config_attr'])
            ->setConditions(['config_cond'])
            ->setPreConditions(['config_pre_cond'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setInitStep(['config_init_step'])
            ->setExecutionStep(['config_execution_step'])
            ->setOrder(77);

        $conditionFactory = $this->getConditionFactory();

        return [
            'no data' => [
                [],
                'expected' => [],
            ],
            'minimum data' => [
                [
                    'minimum_name' => [
                        'label' => 'My Label',
                        'entities' => [
                            'My\Entity'
                        ],
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new Action($conditionFactory, $definition1),
                ],
            ],
            'maximum data' => [
                [
                    'maximum_name' => [
                        'label' => 'My Label',
                        'entities' => ['My\Entity'],
                        'routes' => ['my_route'],
                        'enabled' => false,
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        'conditions' => ['config_cond'],
                        'pre_conditions' => ['config_pre_cond'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'init_step' => ['config_init_step'],
                        'execution_step' => ['config_execution_step'],
                        'order' => 77,
                    ]
                ],
                'expected' => [
                    'maximum_name' => new Action($conditionFactory, $definition2),
                ],
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConditionFactory
     */
    protected function getConditionFactory()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
