<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CheckoutBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionPermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResultRecordInterface */
    protected $record;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionPermissionProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->record = $this->createMock(ResultRecordInterface::class);

        $this->provider = new ActionPermissionProvider();
    }

    /**
     * @dataProvider getPermissionsProvider
     *
     * @param bool $completed
     * @param bool $expected
     */
    public function testGetPermissions($completed, $expected)
    {
        $this->record->expects($this->once())->method('getValue')->with('completed')->willReturn($completed);

        $result = $this->provider->getPermissions($this->record);

        $this->assertArrayHasKey('view', $result);
        $this->assertEquals($expected, $result['view']);
    }

    /**
     * @return array
     */
    public function getPermissionsProvider()
    {
        return [
            'checkout not completed' => [
                'completed' => false,
                'expected' => true
            ],
            'checkout completed' => [
                'completed' => true,
                'expected' => false
            ]
        ];
    }
}
