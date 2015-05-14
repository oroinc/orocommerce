<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

use OroB2B\Bundle\UserAdminBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionPermissionProvider
     */
    protected $actionPermissionProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResultRecordInterface
     */
    protected $record;

    /**
     * @var array
     */
    protected $actionsList = [
        'enable',
        'disable',
        'view',
        'update',
        'delete'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $this->actionPermissionProvider = new ActionPermissionProvider();
    }

    /**
     * @param boolean $isRecordEnabled
     * @param array $expected
     * @dataProvider recordConditions
     */
    public function testGetRequestStatusDefinitionPermissions($isRecordEnabled, array $expected)
    {
        $this->record->expects($this->once())
            ->method('getValue')
            ->with('enabled')
            ->willReturn($isRecordEnabled);

        $result = $this->actionPermissionProvider->getUserPermissions($this->record);

        $this->assertCount(count($this->actionsList), $result);
        foreach ($this->actionsList as $action) {
            $this->assertArrayHasKey($action, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function recordConditions()
    {
        return [
            'enabled record' => [
                'isRecordEnabled' => true,
                'expected' => [
                    'enable' => false,
                    'disable' => true,
                    'view' => true,
                    'update' => true,
                    'delete' => true
                ]
            ],
            'disabled record' => [
                'isRecordEnabled' => false,
                'expected' => [
                    'enable' => true,
                    'disable' => false,
                    'view' => true,
                    'update' => true,
                    'delete' => true
                ]
            ]
        ];
    }
}
