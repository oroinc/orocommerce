<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\MyShoppingListGridEventListener;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MyShoppingListGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $unitFormatter;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var FrontendProductPricesDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productPricesDataProvider;

    /** @var ConfigurableProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableProductProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var LineItemViolationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $violationsProvider;

    /** @var MyShoppingListGridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShoppingListRepository::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $this->query = $this->createMock(AbstractQuery::class);
        $this->query->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->unitFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->productPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->configurableProductProvider = $this->createMock(ConfigurableProductProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->violationsProvider = $this->createMock(LineItemViolationsProvider::class);

        $this->listener = new MyShoppingListGridEventListener(
            $this->urlGenerator,
            $this->eventDispatcher,
            $this->numberFormatter,
            $this->unitFormatter,
            $this->attachmentManager,
            $this->productPricesDataProvider,
            $this->configurableProductProvider,
            $this->localizationHelper,
            $this->violationsProvider
        );
    }

    public function testOnResultAfter(): void
    {
        $product1 = $this->getProduct(101);
        $product2 = $this->getProduct(202);
        $product3 = $this->getProduct(303);

        $lineItem1 = $this->getLineItem(1001, $product1, 'item');
        $lineItem2 = $this->getLineItem(2002, $product2, 'item');
        $lineItem3 = $this->getLineItem(3003, $product3, 'item');

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

        $errors = ['error1'];
        $this->violationsProvider->expects($this->once())
            ->method('getLineItemErrors')
            ->with(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]))
            ->willReturn($errors);

        $record1 = new ResultRecord(['lineItemIds' => $lineItem1->getId()]);
        $record2 = new ResultRecord(['lineItemIds' => $lineItem2->getId() . ',' . $lineItem3->getId() . ',4']);

        $this->listener->onResultAfter(new OrmResultAfter(
            new Datagrid(
                'test-grid',
                DatagridConfiguration::create([]),
                new ParameterBag(['shopping_list_id' => $shoppingList->getId()])
            ),
            [$record1, $record2],
            $this->query
        ));

        $this->assertEquals($product1->getId(), $record1->getValue('productId'));
        $this->assertEquals($product2->getId(), $record2->getValue('productId'));
    }

    /**
     * @param int $id
     * @param Product $product
     * @param string $unitCode
     * @return LineItem
     */
    private function getLineItem(int $id, Product $product, string $unitCode): LineItem
    {
        return $this->getEntity(
            LineItem::class,
            ['id' => $id, 'product' => $product, 'unit' => $this->getProductUnit($unitCode)]
        );
    }

    /**
     * @param int $id
     * @return Product
     */
    private function getProduct(int $id): Product
    {
        return $this->getEntity(
            Product::class,
            [
                'id' => $id,
                'sku' => 'TEST' . $id,
                'inventory_status' => new InventoryStatusStub(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock')
            ]
        );
    }

    /**
     * @param string $code
     * @return ProductUnit
     */
    private function getProductUnit(string $code): ProductUnit
    {
        return $this->getEntity(ProductUnit::class, ['code' => $code, 'defaultPrecision' => 0]);
    }
}
