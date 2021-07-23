<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuickAddCheckoutProcessorTest extends AbstractQuickAddProcessorTest
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListLimitManager
     */
    protected $shoppingListLimitManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CurrentShoppingListManager
     */
    protected $currentShoppingListManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ActionGroupRegistry
     */
    protected $actionGroupRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ActionGroup
     */
    protected $actionGroup;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DateTimeFormatterInterface
     */
    protected $dateFormatter;

    /**
     * @var QuickAddCheckoutProcessor
     */
    protected $processor;

    /**
     * @return string
     */
    public function getProcessorName()
    {
        return QuickAddCheckoutProcessor::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->shoppingListManager = $this->getMockBuilder(ShoppingListManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->shoppingListLimitManager = $this->getMockBuilder(ShoppingListLimitManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->currentShoppingListManager = $this->getMockBuilder(CurrentShoppingListManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->actionGroupRegistry = $this->getMockBuilder(ActionGroupRegistry::class)
            ->disableOriginalConstructor()->getMock();
        $this->actionGroup = $this->getMockBuilder(ActionGroup::class)
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->dateFormatter = $this->getMockBuilder(DateTimeFormatterInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor = new QuickAddCheckoutProcessor(
            $this->handler,
            $this->registry,
            $this->messageGenerator,
            $this->aclHelper
        );

        $this->processor->setProductClass('Oro\Bundle\ProductBundle\Entity\Product');
        $this->processor->setShoppingListManager($this->shoppingListManager);
        $this->processor->setShoppingListLimitManager($this->shoppingListLimitManager);
        $this->processor->setCurrentShoppingListManager($this->currentShoppingListManager);
        $this->processor->setActionGroupRegistry($this->actionGroupRegistry);
        $this->processor->setTranslator($this->translator);
        $this->processor->setDateFormatter($this->dateFormatter);
        $this->processor->setActionGroupName('start_shoppinglist_checkout');
    }

    public function testIsAllowed()
    {
        $this->handler->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->actionGroupRegistry->expects($this->once())->method('findByName')
            ->with('start_shoppinglist_checkout')->willReturn($this->actionGroup);

        $this->assertTrue($this->processor->isAllowed());
    }

    public function testIsAllowedAndNoActionGroup()
    {
        $this->handler->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->actionGroupRegistry->expects($this->once())->method('findByName')
            ->with('start_shoppinglist_checkout')->willReturn(null);

        $this->assertFalse($this->processor->isAllowed());
    }

    /**
     * @dataProvider wrongDataDataProvider
     * @param array $data
     */
    public function testProcessWithNotValidData($data)
    {
        /** @var Request $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(null, $this->processor->process($data, $request));
    }

    /**
     * @return array
     */
    public function wrongDataDataProvider()
    {
        return [
            'entity items are not array' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => 'something'
                ]
            ],
            'entity items are not array and empty' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => ''
                ]
            ],
            'entity items are empty' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => []
                ]
            ],
        ];
    }

    public function testProcessWhenCommitted()
    {
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

        $this->shoppingListLimitManager
            ->expects($this->once())
            ->method('isReachedLimit')
            ->willReturn(false);

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->dateFormatter->expects($this->once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $redirectUrl = '/customer/shoppingList/123';
        $actionData = new ActionData([
            'shoppingList' => $shoppingList,
            'redirectUrl' => $redirectUrl
        ]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects($this->once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        $this->actionGroup->expects($this->once())
            ->method('execute')
            ->willReturn($actionData);

        $this->handler->expects($this->once())
            ->method('createForShoppingList')
            ->with(
                $this->isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(count($data));

        $this->em
            ->expects($this->once())
            ->method('commit');

        /** @var RedirectResponse $result */
        $result = $this->processor->process($data, new Request());
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals($redirectUrl, $result->getTargetUrl());
    }

    public function testProcessWhenCommittedWithLimit()
    {
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

        $this->shoppingListLimitManager
            ->expects($this->once())
            ->method('isReachedLimit')
            ->willReturn(true);

        $shoppingList = new ShoppingList();

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->shoppingListManager->expects($this->once())
            ->method('edit')
            ->willReturn($shoppingList);

        $this->shoppingListManager->expects($this->once())
            ->method('removeLineItems');

        $this->em
            ->expects($this->never())
            ->method('persist');

        $this->em
            ->expects($this->never())
            ->method('flush');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects($this->once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $actionData = new ActionData([
            'shoppingList' => $shoppingList,
            'redirectUrl' => 'some/url'
        ]);

        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        $this->actionGroup->expects($this->once())
            ->method('execute')
            ->willReturn($actionData);

        $this->handler->expects($this->once())
            ->method('createForShoppingList')
            ->with(
                $this->isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(count($data));

        $this->processor->process($data, new Request());
    }

    public function testProcessWhenActionGroupFailedWithErrors()
    {
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->dateFormatter->expects($this->once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $actionData = new ActionData([
            'shoppingList' => $shoppingList,
            'redirectUrl' => null,
            'errors' => []
        ]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects($this->once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        $this->actionGroup->expects($this->once())
            ->method('execute')
            ->willReturn($actionData);

        $this->handler->expects($this->once())
            ->method('createForShoppingList')
            ->with(
                $this->isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(count($data));

        $request = new Request();
        $this->assertFailedFlashMessage($request);

        $this->em
            ->expects($this->once())
            ->method('rollback');

        $this->assertFalse($this->processor->process($data, $request));
    }

    public function testProcessWhenHandlerThrowsException()
    {
        $data = $this->getProductData();

        $shoppingList = new ShoppingList();
        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->dateFormatter->expects($this->once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects($this->once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->handler->expects($this->once())
            ->method('createForShoppingList')
            ->willThrowException(new AccessDeniedException());

        $request = new Request();
        $this->assertFailedFlashMessage($request);

        $this->em
            ->expects($this->once())
            ->method('rollback');

        $this->assertEquals(null, $this->processor->process($data, $request));
    }

    public function testProcessWhenNoItemsCreatedForShoppingList()
    {
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->dateFormatter->expects($this->once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects($this->once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->handler->expects($this->once())
            ->method('createForShoppingList')
            ->with(
                $this->isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(0);

        $this->em
            ->expects($this->once())
            ->method('rollback');

        $request = new Request();
        $this->assertFailedFlashMessage($request);

        $this->assertEquals(null, $this->processor->process($data, $request));
    }

    protected function assertFailedFlashMessage(Request $request)
    {
        $message = 'failed message';

        $this->messageGenerator->expects($this->once())
            ->method('getFailedMessage')
            ->willReturn($message);

        $flashBag = $this->createMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error')
            ->willReturn($flashBag);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Session $session */
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $request->setSession($session);
    }

    /**
     * @return array
     */
    protected function getProductData()
    {
        return [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
                ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
            ]
        ];
    }
}
