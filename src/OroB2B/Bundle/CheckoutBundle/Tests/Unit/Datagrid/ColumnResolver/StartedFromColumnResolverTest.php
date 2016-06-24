<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid\ColumnResolver;

use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnResolver\StartedFromColumnResolver;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class StartedFromColumnResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testUseRepositoryToResolveColumnValue()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('test');

        $quote = new Quote();

        $repository->expects($this->atLeastOnce())
            ->method('getSourcesByIds')
            ->will($this->returnValue([
                3 => $shoppingList,
                2 => $quote
            ]));

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
            ->disableOriginalConstructor()
            ->getMock();

        $records = [];

        $foundSources = [];

        $record = $this->getMockBuilder('\StdClass')
            ->setMethods(['getValue', 'addData'])
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

        $record = $this->getMockBuilder('\StdClass')
            ->setMethods(['getValue', 'addData'])
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

        $event->method('getRecords')
            ->will($this->returnValue($records));

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $translator->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnValue('Quote'));

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
                ->disableOriginalConstructor()
                ->getMock();

        $startedFromColumnResolver = new StartedFromColumnResolver(
            $repository,
            $translator,
            $securityFacade
        );

        $startedFromColumnResolver->resolveColumn($event);

        $foundShoppingList = false;

        foreach ($foundSources as $source) {
            if ($source['startedFrom']->getLabel() == $shoppingList->getLabel()) {
                $foundShoppingList = true;
            }
        }

        $this->assertTrue($foundShoppingList);

        $foundQuote = false;

        foreach ($foundSources as $source) {
            if (strstr($source['startedFrom']->getLabel(), 'Quote')) {
                $foundQuote = true;
            }
        }

        $this->assertTrue($foundQuote);
    }
}
