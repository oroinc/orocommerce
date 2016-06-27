<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid\ColumnBuilder;

use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder\StartedFromColumnBuilder;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class StartedFromColumnBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testUseRepositoryToBuildColumnValue()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository')
                           ->disableOriginalConstructor()
                           ->getMock();

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('test');

        $quote = new Quote();

        $repository->expects($this->atLeastOnce())
                   ->method('getSourcePerCheckout')
                   ->will($this->returnValue([
                                                 3 => $shoppingList,
                                                 2 => $quote
                                             ]));

        $records = [ ];

        $foundSources = [ ];

        $record = $this->getMockBuilder('\StdClass')
                       ->setMethods([ 'getValue', 'addData' ])
                       ->getMock();

        $record->expects($this->atLeastOnce())
               ->method('getValue')
               ->will($this->returnValue(3));

        $record->expects($this->atLeastOnce())
               ->method('addData')
               ->will($this->returnCallback(function ($value) use (& $foundSources) {
                   $foundSources[] = $value;
               }));

        $records[] = $record;

        $record = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\ResultRecord')
                       ->disableOriginalConstructor()
                       ->setMethods([ 'getValue', 'addData' ])
                       ->getMock();

        $record->expects($this->atLeastOnce())
               ->method('getValue')
               ->will($this->returnValue(2));

        $record->expects($this->atLeastOnce())
               ->method('addData')
               ->will($this->returnCallback(function ($value) use (& $foundSources) {
                   $foundSources[] = $value;
               }));

        $records[] = $record;

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
                           ->disableOriginalConstructor()
                           ->getMock();

        $translator->expects($this->atLeastOnce())
                   ->method('trans')
                   ->will($this->returnValue('Quote'));

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
                               ->disableOriginalConstructor()
                               ->getMock();

        $startedFromColumnBuilder = new StartedFromColumnBuilder(
            $repository,
            $translator,
            $securityFacade
        );

        $startedFromColumnBuilder->buildColumn($records);

        $foundShoppingList = false;

        foreach ($foundSources as $source) {
            if ($source['startedFrom']->getLabel() == $shoppingList->getLabel()) {
                $foundShoppingList = true;
            }
        }

        $this->assertTrue($foundShoppingList, 'Did not found any ShoppingList entity');

        $foundQuote = false;

        foreach ($foundSources as $source) {
            if (strstr($source['startedFrom']->getLabel(), 'Quote')) {
                $foundQuote = true;
            }
        }

        $this->assertTrue($foundQuote, 'Did not found any Quote entity');
    }
}
