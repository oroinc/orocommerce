<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinition;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSourceFieldListener;

class CheckoutSourceFieldListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testUsesDefinersToDefineSourceItems()
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

        $checkoutSourceFieldListener = new CheckoutSourceFieldListener($doctrine);

        for ($i=0; $i<3; $i++) {
            $definitionResolver = $this->getMock(
                'OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinitionResolverInterface'
            );

            $definitionResolver->expects($this->once())
                ->method('loadSources')
                ->will($this->returnValue([$i => new CheckoutSourceDefinition('test', false)]));

            $checkoutSourceFieldListener->addSourceDefinitionResolver($definitionResolver);
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

        $checkoutSourceFieldListener->onResultAfter($ormResultSet);
    }
}
