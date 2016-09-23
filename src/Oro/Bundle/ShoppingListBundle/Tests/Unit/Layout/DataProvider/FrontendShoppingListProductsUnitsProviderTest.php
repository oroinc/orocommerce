<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsUnitsProvider;

class FrontendShoppingListProductsUnitsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TEST_CURRENCY = 'USD';

    /**
     * @var FrontendShoppingListProductsUnitsProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $requestHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserCurrencyManager
     */
    protected $userCurrencyManager;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestHandler = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userCurrencyManager = $this->getMockBuilder('Oro\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductsUnitsProvider(
            $this->registry,
            $this->requestHandler,
            $this->userCurrencyManager
        );
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ShoppingList|null $shoppingList
     * @param array|null $expected
     */
    public function testGetData($shoppingList, $expected)
    {
        if ($shoppingList) {
            $repository = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $repository->expects($this->once())
                ->method('getProductsUnits')
                ->willReturn($expected);

            $em = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
            $em->expects($this->once())
                ->method('getRepository')
                ->with('OroProductBundle:ProductUnit')
                ->willReturn($repository);

            $this->registry->expects($this->once())
                ->method('getManagerForClass')
                ->with('OroProductBundle:ProductUnit')
                ->willReturn($em);
        }

        $actual = $this->provider->getProductsUnits($shoppingList);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        /** @var Product $product1 */
        $product1 = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id'=> 123]);
        /** @var Product $product2 */
        $product2 = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id'=> 321]);

        $lineItem1 = new LineItem();
        $lineItem1->setProduct($product1);

        $lineItem2 = new LineItem();
        $lineItem2->setProduct($product2);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

        return [
            [
                'entity' => $shoppingList,
                'expected' => [
                    '123' => ['liter', 'bottle'],
                    '321' => ['piece' ]
                ]
            ],
            [
                'entity' => null,
                'expected' => null
            ]
        ];
    }
}
