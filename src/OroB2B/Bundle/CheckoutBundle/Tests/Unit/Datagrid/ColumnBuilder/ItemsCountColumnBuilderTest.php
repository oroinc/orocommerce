<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid\ColumnBuilder;

use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder\ItemsCountColumnBuilder;

class ItemsCountColumnBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testUsesRepositoryToBuildColumnValue()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository')
                           ->disableOriginalConstructor()
                           ->getMock();

        $repository->expects($this->atLeastOnce())
                   ->method('countItemsPerCheckout')
                   ->will($this->returnValue([
                                                 1 => 4,
                                                 2 => 3
                                             ]));

        $records = [ ];

        $records[] = $this->createRecord();
        $records[] = $this->createRecord();

        $itemsCountBuilder = new ItemsCountColumnBuilder($repository);
        $itemsCountBuilder->buildColumn($records);
    }

    /**
     * @return mixed
     */
    private function createRecord()
    {
        $record = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\ResultRecord')
                       ->disableOriginalConstructor()
                       ->setMethods([ 'getValue', 'addData' ])
                       ->getMock();

        $record->expects($this->atLeastOnce())
               ->method('getValue')
               ->will($this->returnValue(1));

        $record->expects($this->atLeastOnce())
               ->method('addData');

        return $record;
    }
}
