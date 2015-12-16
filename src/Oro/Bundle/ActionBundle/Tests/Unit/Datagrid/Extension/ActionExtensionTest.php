<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\ActionExtension;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Model\ContextHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper $contextHelper */
        $contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $actionData = new ActionData(['data' => ['param']]);

        $contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn($actionData);

        $this->extension = new ActionExtension($this->manager, $contextHelper);
    }

    /**
     * @param array $configData
     * @param Action[] $actions
     * @param bool $expected
     *
     * @internal param array $input
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(array $configData, array $actions, $expected)
    {
        $this->manager->expects($this->any())
            ->method('getActions')
            ->willReturn($actions);
        $config = DatagridConfiguration::create($configData);
        $this->assertEquals(
            $expected,
            $this->extension->isApplicable(
                $config
            )
        );
        if ($expected) {
            $this->assertNotEmpty($config->offsetGetOr('actions'));
            $this->assertNotEmpty($config->offsetGetOr('action_configuration'));
        }
    }

    /**
     * @param ResultRecord[] $records
     * @param Action[] $actions
     * @param int $expectedActionsCnt
     *
     * @dataProvider visitResultProvider
     */
    public function testVisitResult(array $records, array $actions, $expectedActionsCnt)
    {
        $result = ResultsObject::create(['data' => $records]);

        $this->manager->expects($this->any())
            ->method('getActions')
            ->willReturn($actions);
        $config = DatagridConfiguration::create([]);


        $this->extension->visitResult($config, $result);
        /** @var ResultRecord[] $rows */
        $rows = $result->offsetGetByPath('[data]', []);
        foreach ($rows as $record) {
            $this->assertEquals($expectedActionsCnt, count($record->getValue('actions')));
        }
    }

    /**
     * @param ResultRecord $record
     * @param array $expectedActions
     *
     * @dataProvider getActionsPermissionsProvider
     */
    public function testGetActionsPermissions(ResultRecord $record, array $expectedActions)
    {
        $this->assertEquals($expectedActions, $this->extension->getActionsPermissions($record));
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
    {
        return [
            'applicable' => [
                'configData' => [
                    'name' => ' datagrid1',
                ],
                'actions' => [$this->createAction()],
                'expected' => true
            ],
            'not applicable' => [
                'configData' => [
                    'name' => ' datagrid1',
                ],
                'actions' => [],
                'expected' => false
            ]
        ];
    }

    /**
     * @return array
     */
    public function visitResultProvider()
    {
        $record1 = new ResultRecord(['id' => 1]);
        $record2 = new ResultRecord(['id' => 2]);
        $actionAllowed1 = $this->createAction('action1', true);
        $actionAllowed2 = $this->createAction('action2', true);
        $actionNotAllowed = $this->createAction('action3', false);

        return [
            'no records' => [
                'records' => [],
                'actions' => [$actionAllowed1],
                'expectedActionsCnt' => 0,
            ],
            'no actions' => [
                'records' => [$record1],
                'actions' => [],
                'expectedActionsCnt' => 0,
            ],
            '2 allowed actions' => [
                'records' => [$record1, $record2],
                'actions' => [$actionAllowed1, $actionAllowed2],
                'expectedActionsCnt' => 2
            ],
            '1 allowed action' => [
                'records' => [],
                'actions' => [$actionAllowed1, $actionNotAllowed],
                'expectedActionsCnt' => 1
            ]
        ];
    }

    /**
     * @return array
     */
    public function getActionsPermissionsProvider()
    {
        $actions1 = [];
        $actions2 = ['action1'];
        $actions3 = ['action1', 'action2'];

        $record1 = new ResultRecord(['id' => 1, 'actions' => $actions1]);
        $record2 = new ResultRecord(['id' => 1, 'actions' => $actions2]);
        $record3 = new ResultRecord(['id' => 1, 'actions' => $actions3]);

        return [
            'no actions' => [
                'record' => $record1,
                'expectedActions' => $actions1,
            ],
            '1 action' => [
                'record' => $record2,
                'expectedActions' => $actions2,
            ],
            '2 actions' => [
                'record' => $record3,
                'expectedActions' => $actions3,
            ],

        ];
    }

    /**
     * @param string $name
     * @param bool $isAllowed
     *
     * @return Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAction($name = 'test_action', $isAllowed = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $definition */
        $definition = $this->getMock('Oro\Bundle\ActionBundle\Model\ActionDefinition');
        /** @var \PHPUnit_Framework_MockObject_MockObject|Action $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $action->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $action->expects($this->any())
            ->method('isAllowed')->withAnyParameters()
            ->willReturn($isAllowed);

        return $action;
    }
}
