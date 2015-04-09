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
     * @var ResultRecordInterface
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
     */
    public function testGetRequestStatusDefinitionPermissions()
    {
        $this->record->expects($this->once())
            ->method('getValue')
            ->with('deleted')
            ->willReturn(true);

        $this->assertCount(3, $this->actionPermissionProvider->getRequestStatusDefinitionPermissions($this->record));
    }
}
