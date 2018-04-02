<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

class QuickAddCheckoutProcessorTest extends AbstractQuickAddProcessorTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListLimitManager
     */
    protected $shoppingListLimitManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupRegistry
     */
    protected $actionGroupRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ActionGroup
     */
    protected $actionGroup;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DateTimeFormatter
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
    protected function setUp()
    {
        parent::setUp();

        $this->shoppingListManager = $this->getMockBuilder(ShoppingListManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->shoppingListLimitManager = $this->getMockBuilder(ShoppingListLimitManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->actionGroupRegistry = $this->getMockBuilder(ActionGroupRegistry::class)
            ->disableOriginalConstructor()->getMock();
        $this->actionGroup = $this->getMockBuilder(ActionGroup::class)
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->dateFormatter = $this->getMockBuilder(DateTimeFormatter::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor = new QuickAddCheckoutProcessor($this->handler, $this->registry, $this->messageGenerator);

        $this->processor->setProductClass('Oro\Bundle\ProductBundle\Entity\Product');
        $this->processor->setShoppingListManager($this->shoppingListManager);
        $this->processor->setShoppingListLimitManager($this->shoppingListLimitManager);
        $this->processor->setActionGroupRegistry($this->actionGroupRegistry);
        $this->processor->setTranslator($this->translator);
        $this->processor->setDateFormatter($this->dateFormatter);
        $this->processor->setActionGroupName('start_shoppinglist_checkout');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset(
            $this->shoppingListManager,
            $this->shoppingListLimitManager,
            $this->actionGroupRegistry,
            $this->actionGroup,
            $this->translator,
            $this->dateFormatter
        );
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

        $productIds = ['sku1' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

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

        $productIds = ['sku1' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

        $this->shoppingListLimitManager
            ->expects($this->once())
            ->method('isReachedLimit')
            ->willReturn(true);

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects($this->once())
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

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

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

        $productIds = ['sku1' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

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

        $productIds = ['sku1' => 1, 'sku2' => 2];

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

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

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

        $productIds = ['sku1' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

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

    /**
     * @param Request $request
     */
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|Session $session */
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
                ['productSku' => 'sku1', 'productQuantity' => 2, 'productUnit' => 'kg'],
                ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
            ]
        ];
    }
}
