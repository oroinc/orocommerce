<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

use OroB2B\Bundle\RFPAdminBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_DEFAULT_STATUS = 'default_status';

    /**
     * @var ActionPermissionProvider
     */
    protected $actionPermissionProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResultRecordInterface
     */
    protected $record;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $actionsList = [
        'restore',
        'delete',
        'view',
        'update'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_request_status')
            ->will($this->returnValue(self::CONFIG_DEFAULT_STATUS));

        $this->actionPermissionProvider = new ActionPermissionProvider($this->configManager);
    }

    /**
     * Test getRequestStatusDefinitionPermissions
     *
     * @dataProvider recordConditions
     * @param boolean $isRecordDeleted
     * @param string $name
     */
    public function testGetRequestStatusDefinitionPermissions($isRecordDeleted, $name)
    {
        $this->record->expects($this->at(0))
            ->method('getValue')
            ->with('deleted')
            ->willReturn($isRecordDeleted);

        $this->record->expects($this->at(1))
            ->method('getValue')
            ->with('name')
            ->willReturn($name);

        $result = $this->actionPermissionProvider->getRequestStatusDefinitionPermissions($this->record);

        $this->assertCount(count($this->actionsList), $result);
        foreach ($this->actionsList as $action) {
            $this->assertArrayHasKey($action, $result);
        }

        $isDefaultStatus = ($name == self::CONFIG_DEFAULT_STATUS);

        $this->assertEquals($isRecordDeleted, $result['restore']);
        $this->assertEquals(!$isRecordDeleted && !$isDefaultStatus, $result['delete']);
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
                'name' => 'open'
            ],
            'normal record' => [
                'isRecordDeleted' => false,
                'closed' => 'closed'
            ],
            'config default status' => [
                'isRecordDeleted' => false,
                'name' => self::CONFIG_DEFAULT_STATUS
            ]
        ];
    }
}
