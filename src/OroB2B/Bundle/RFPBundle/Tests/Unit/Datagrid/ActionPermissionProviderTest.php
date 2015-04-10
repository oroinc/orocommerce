<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

use OroB2B\Bundle\RFPBundle\Datagrid\ActionPermissionProvider;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $this->actionPermissionProvider = new ActionPermissionProvider();
    }

    /**
     * Test getRequestStatusDefinitionPermissions
     *
     * @dataProvider recordConditions
     * @param $isRecordDeleted
     */
    public function testGetRequestStatusDefinitionPermissions($isRecordDeleted)
    {
        $this->record->expects($this->once())
            ->method('getValue')
            ->with('deleted')
            ->willReturn($isRecordDeleted);

        $result = $this->actionPermissionProvider->getRequestStatusDefinitionPermissions($this->record);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('restore', $result);
        $this->assertArrayHasKey('delete', $result);
        $this->assertArrayHasKey('view', $result);

        $this->assertEquals($isRecordDeleted, $result['restore']);
        $this->assertEquals(!$isRecordDeleted, $result['delete']);
        $this->assertTrue($result['view']);
    }

    /**
     * @return array
     */
    public function recordConditions()
    {
        return [
            'deleted record' => [
                'isRecordDeleted' => true,
            ],
            'normal record' => [
                'isRecordDeleted' => false,
            ],
        ];
    }
}
