<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\MyShoppingListGridEventListener;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class MyShoppingListGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var FrontendProductPricesDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productPricesDataProvider;

    /** @var ProductPriceFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceFormatter;

    /** @var ConfigurableProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableProductProvider;

    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShoppingListRepository::class);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($objectManager);

        $this->productPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->productPriceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->configurableProductProvider = $this->createMock(ConfigurableProductProvider::class);

        $this->listener = new MyShoppingListGridEventListener(
            $registry,
            $this->productPricesDataProvider,
            $this->productPriceFormatter,
            $this->configurableProductProvider
        );
    }

    public function testOnResultAfter(): void
    {
        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 1001]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 2002]);
        $lineItem3 = $this->getEntity(LineItem::class, ['id' => 3003]);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 42]);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);
        $shoppingList->addLineItem($lineItem3);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($shoppingList->getId())
            ->willReturn($shoppingList);

        $matchedPrices = ['matchedPrices'];
        $this->productPricesDataProvider->expects($this->once())
            ->method('getProductsMatchedPrice')
            ->with([$lineItem1, $lineItem2, $lineItem3])
            ->willReturn($matchedPrices);

        $productPrices = ['productPrices'];
        $this->productPricesDataProvider->expects($this->once())
            ->method('getProductsAllPrices')
            ->with([$lineItem1, $lineItem2, $lineItem3])
            ->willReturn($productPrices);

        $allPrices = ['allPrices'];
        $this->productPriceFormatter->expects($this->once())
            ->method('formatProducts')
            ->with($productPrices)
            ->willReturn($allPrices);

        $configurableProducts = ['configurableProducts'];
        $this->configurableProductProvider->expects($this->once())
            ->method('getProducts')
            ->with([$lineItem1, $lineItem2, $lineItem3])
            ->willReturn($configurableProducts);

        $record1 = new ResultRecord(['lineItemIds' => $lineItem1->getId()]);
        $record2 = new ResultRecord(['lineItemIds' => $lineItem2->getId() . ',' . $lineItem3->getId() . ',4']);

        $this->listener->onResultAfter(new OrmResultAfter(
            new Datagrid(
                'test-grid',
                DatagridConfiguration::create([]),
                new ParameterBag(['shopping_list_id' => $shoppingList->getId()])
            ),
            [$record1, $record2]
        ));

        $this->assertEquals([$lineItem1], $record1->getValue('lineItems'));
        $this->assertEquals($matchedPrices, $record1->getValue('matchedPrices'));
        $this->assertEquals($allPrices, $record1->getValue('allPrices'));
        $this->assertEquals($configurableProducts, $record1->getValue('configurableProducts'));

        $this->assertEquals([$lineItem2, $lineItem3], $record2->getValue('lineItems'));
        $this->assertEquals($matchedPrices, $record2->getValue('matchedPrices'));
        $this->assertEquals($allPrices, $record2->getValue('allPrices'));
        $this->assertEquals($configurableProducts, $record2->getValue('configurableProducts'));
    }
}
