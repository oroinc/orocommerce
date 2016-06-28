<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid\ColumnBuilder;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder\TotalColumnBuilder;
use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalColumnBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testUseRepositoryToBuildColumnValue()
    {
        $totalProcessor = $this
            ->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rm = $this->getMockBuilder(RegistryInterface::class)
                   ->disableOriginalConstructor()
                   ->getMock();

        $em = $this->getMockBuilder(EntityRepository::class)
                   ->disableOriginalConstructor()
                   ->getMock();

        $totalProcessor->expects($this->once())->method('getTotal')->willReturn((new Subtotal())->setAmount(10));

        $em->expects($this->once())->method('find')->with(2)->willReturn(new Checkout());

        $rm->expects($this->once())->method('getRepository')->willReturn($em);

        $record1 = new ResultRecord([ 'id' => 1, 'total' => 10 ]);
        $record2 = new ResultRecord([ 'id' => 2 ]);

        $records = [ $record1, $record2 ];

        $testable = new TotalColumnBuilder($rm, $totalProcessor);
        $testable->buildColumn($records);

        $this->assertSame(10, $record2->getValue('total'));

    }
}
