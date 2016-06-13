<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutLineItemsCountListener;

class CheckoutLineItemsCountListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testUsesCountersToCountLineItems()
    {
        $doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine->expects($this->atLeastOnce())
            ->method('getEntityManagerForClass')
            ->will($this->returnValue($em));

        $checkoutLineItemsCounterListener = new CheckoutLineItemsCountListener($doctrine);

        for ($i=0; $i<3; $i++) {
            $counter = $this->getMock(
                'OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutItemsCounters\CheckoutItemsCounterInterface'
            );

            $counter->expects($this->once())
                ->method('countItems')
                ->will($this->returnValue([$i => $i * 2]));

            $checkoutLineItemsCounterListener->addCounter($counter);
        }

        $ormResultSet = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
            ->setMethods(['getRecords'])
            ->disableOriginalConstructor()
            ->getMock();

        $results = [];

        for ($i=0; $i<5; $i++) {
            $result = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\ResultRecordInterface')
                ->setMethods(['getValue', 'addData'])
                ->disableOriginalConstructor()
                ->getMock();

            $result->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue('test'));

            $result->expects($this->atLeastOnce())
                ->method('addData');

            $results[] = $result;
        }

        $ormResultSet->expects($this->atLeastOnce())
            ->method('getRecords')
            ->will($this->returnValue($results));

        $checkoutLineItemsCounterListener->onResultAfter($ormResultSet);
    }
}
