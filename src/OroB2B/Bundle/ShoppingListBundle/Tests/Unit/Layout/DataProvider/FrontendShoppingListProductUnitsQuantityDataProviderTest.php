<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityDataProvider;

class FrontendShoppingListProductUnitsQuantityDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const SHOPPING_LIST_CLASS_NAME = 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList';
    const LINE_ITEM_CLASS_NAME = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var FrontendShoppingListProductUnitsQuantityDataProvider */
    protected $provider;

    /** @var ShoppingListRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingListRepository;

    /** @var LineItemRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItemRepository;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shoppingListRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [self::SHOPPING_LIST_CLASS_NAME, null, $this->shoppingListRepository],
                [self::LINE_ITEM_CLASS_NAME, null, $this->lineItemRepository],
            ]);

        $this->provider = new FrontendShoppingListProductUnitsQuantityDataProvider(
            $this->securityFacade,
            $this->registry,
            self::SHOPPING_LIST_CLASS_NAME,
            self::LINE_ITEM_CLASS_NAME
        );
    }

    protected function tearDown()
    {
        unset($this->provider, $this->securityFacade, $this->registry);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param Product|null $product
     * @param ShoppingList|null $shoppingList
     * @param UserInterface $user
     * @param array $lineItems
     * @param array|null $expected
     */
    public function testGetData(
        $product,
        $shoppingList,
        UserInterface $user,
        array $lineItems = [],
        array $expected = null
    ) {
        $context = new LayoutContext();
        $context->data()->set('product', null, $product);

        $this->securityFacade->expects($product ? $this->once() : $this->never())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->shoppingListRepository->expects($user instanceof AccountUser ? $this->once() : $this->never())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn($shoppingList);

        $this->lineItemRepository
            ->expects($shoppingList && $user instanceof AccountUser ? $this->once() : $this->never())
            ->method('getItemsByShoppingListAndProduct')
            ->with($shoppingList, $product)
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getData($context));
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            [
                'product' => null,
                'shoppingList' => null,
                'user' => $this->getEntity('Oro\Bundle\UserBundle\Entity\User')
            ],
            [
                'product' => new Product(),
                'shoppingList' => null,
                'user' => $this->getEntity('Oro\Bundle\UserBundle\Entity\User')
            ],
            [
                'product' => new Product(),
                'shoppingList' => new ShoppingList(),
                'user' => $this->getEntity('Oro\Bundle\UserBundle\Entity\User')
            ],
            [
                'product' => new Product(),
                'shoppingList' => new ShoppingList(),
                'user' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser'),
                'lineItems' => [],
                'expected' => []
            ],
            [
                'product' => new Product(),
                'shoppingList' => new ShoppingList(),
                'user' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser'),
                'lineItems' => [
                    $this->getEntity(
                        self::LINE_ITEM_CLASS_NAME,
                        [
                            'unit' => $this->getEntity(
                                'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'code1']
                            ),
                            'quantity' => 42
                        ]
                    ),
                    $this->getEntity(
                        self::LINE_ITEM_CLASS_NAME,
                        [
                            'unit' => $this->getEntity(
                                'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'code2']
                            ),
                            'quantity' => 100
                        ]
                    )
                ],
                'expected' => [
                    'code1' => 42,
                    'code2' => 100
                ]
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: product.
     */
    public function testGetDataWithEmptyContext()
    {
        $this->provider->getData(new LayoutContext());
    }
}
