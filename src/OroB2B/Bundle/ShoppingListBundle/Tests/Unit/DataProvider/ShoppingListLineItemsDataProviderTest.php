<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;

class ShoppingListLineItemsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShoppingListLineItemsDataProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->setMethods(['getManagerForClass'])->disableOriginalConstructor()->getMockForAbstractClass();

        $this->provider = new ShoppingListLineItemsDataProvider($this->registry);
    }

    public function testGetShoppingListLineItems()
    {
        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 1]),
        ];

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 2]);

        $repo = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getItemsWithProductByShoppingList')
            ->with($shoppingList)
            ->willReturn($lineItems);

        $em = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($lineItems, $this->provider->getShoppingListLineItems($shoppingList));
        // Second assert are using to be sure that local cache is used
        $this->assertEquals($lineItems, $this->provider->getShoppingListLineItems($shoppingList));
    }
}
