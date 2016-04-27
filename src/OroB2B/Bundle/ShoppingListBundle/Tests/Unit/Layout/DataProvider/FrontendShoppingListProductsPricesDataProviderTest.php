<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsPricesDataProvider;

class FrontendShoppingListProductsPricesDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var  FrontendShoppingListProductsPricesDataProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    public function setUp()
    {
        $this->frontendProductPricesDataProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->provider = new FrontendShoppingListProductsPricesDataProvider(
            $this->frontendProductPricesDataProvider,
            $this->registry
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: entity.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ShoppingList|null $shoppingList
     */
    public function testGetData($shoppingList)
    {
        $context = new LayoutContext();
        $context->data()->set('entity', null, $shoppingList);
        $expected = null;

        if ($shoppingList) {
            $lineItems = [];
            $repo = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $repo->expects($this->once())
                ->method('getItemsWithProductByShoppingList')
                ->with($shoppingList)
                ->will($this->returnValue($lineItems));

            $em = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
            $em->expects($this->once())
                ->method('getRepository')
                ->will($this->returnValue($repo));
            $this->registry->expects($this->once())
                ->method('getManagerForClass')
                ->will($this->returnValue($em));

            $expected = 'expectedData';
            $this->frontendProductPricesDataProvider
                ->expects($this->once())
                ->method('getProductsMatchedPrice')
                ->with($lineItems)
                ->willReturn($expected);
        }

        $result = $this->provider->getData($context);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            'with shoppingList' => [
                'entity' => new ShoppingList(),
            ],
            'without shoppingList' => [
                'entity' => null,
            ],
        ];
    }
}
