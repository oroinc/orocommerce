<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid\ColumnResolver;

use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnResolver\ItemsCountColumnResolver;

class ItemsCountColumnResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testUsesRepositoryToResolveColumnValue()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->atLeastOnce())
            ->method('countItemsByIds')
            ->will($this->returnValue([
                1 => 4,
                2 => 3
            ]));

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
            ->disableOriginalConstructor()
            ->getMock();

        $records = [];

        $records[] = $this->createRecord();
        $records[] = $this->createRecord();

        $event->method('getRecords')
            ->will($this->returnValue($records));

        $itemsCountResolver = new ItemsCountColumnResolver($repository);
        $itemsCountResolver->resolveColumn($event);
    }

    /**
     * @return mixed
     */
    private function createRecord()
    {
        $record = $this->getMockBuilder('\StdClass')
            ->setMethods(['getValue', 'addData'])
            ->getMock();

        $record->expects($this->atLeastOnce())
            ->method('getValue')
            ->will($this->returnValue(1));

        $record->expects($this->atLeastOnce())
            ->method('addData');

        return $record;
    }
}
