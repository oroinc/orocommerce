<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartQuickOrderCheckoutInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuickAddCheckoutProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLineItemHandler|MockObject */
    private $shoppingListLineItemHandler;

    /** @var ProductMapperInterface|MockObject */
    private $productMapper;

    /** @var MessageGenerator|MockObject */
    private $messageGenerator;

    /** @var ShoppingListManager|MockObject */
    private $shoppingListManager;

    /** @var ShoppingListLimitManager|MockObject */
    private $shoppingListLimitManager;

    /** @var CurrentShoppingListManager|MockObject */
    private $currentShoppingListManager;

    /** @var TranslatorInterface|MockObject */
    private $translator;

    /** @var DateTimeFormatterInterface|MockObject */
    private $dateFormatter;

    /** @var EntityManagerInterface|MockObject */
    private $em;

    private StartQuickOrderCheckoutInterface|MockObject $startQuickOrderCheckout;

    /** @var AuthorizationCheckerInterface|MockObject */
    private $authorizationChecker;

    /** @var IsWorkflowStartFromShoppingListAllowed|MockObject */
    private $isWorkflowStartFromShoppingListAllowed;

    /** @var QuickAddCheckoutProcessor */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->shoppingListLineItemHandler = $this->createMock(ShoppingListLineItemHandler::class);
        $this->productMapper = $this->createMock(ProductMapperInterface::class);
        $this->messageGenerator = $this->createMock(MessageGenerator::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->dateFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->startQuickOrderCheckout = $this->createMock(StartQuickOrderCheckoutInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->isWorkflowStartFromShoppingListAllowed = $this->createMock(
            IsWorkflowStartFromShoppingListAllowed::class
        );

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->processor = new QuickAddCheckoutProcessor(
            $this->shoppingListLineItemHandler,
            $this->productMapper,
            $doctrine,
            $this->messageGenerator,
            $this->shoppingListManager,
            $this->shoppingListLimitManager,
            $this->currentShoppingListManager,
            $this->translator,
            $this->dateFormatter,
            $this->startQuickOrderCheckout,
            $this->authorizationChecker,
            $this->isWorkflowStartFromShoppingListAllowed
        );
    }

    /**
     * @dataProvider wrongDataDataProvider
     */
    public function testProcessWithNotValidData(array $data): void
    {
        $request = $this->createMock(Request::class);

        self::assertNull($this->processor->process($data, $request));
    }

    public function wrongDataDataProvider(): array
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

    public function testProcessWhenCommitted(): void
    {
        $data = $this->getProductData([
            ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
            ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
        ]);

        $productMap = [1, 2];
        $productIds = [1, 2];
        $productUnitsQuantities = [1 => ['kg' => 2], 2 => ['liter' => 3]];

        $this->shoppingListLimitManager->expects(self::once())
            ->method('isReachedLimit')
            ->willReturn(false);

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->em->expects(self::once())
            ->method('persist');
        $this->em->expects(self::once())
            ->method('flush');

        $this->dateFormatter->expects(self::once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $redirectUrl = '/customer/shoppingList/123';
        $startResult = [
            'redirectUrl' => $redirectUrl
        ];

        $this->expectsMapProducts($productMap);

        $this->startQuickOrderCheckout->expects(self::once())
            ->method('execute')
            ->with($shoppingList, 'start_transition')
            ->willReturn($startResult);

        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('createForShoppingList')
            ->with(self::isInstanceOf(ShoppingList::class), $productIds, $productUnitsQuantities)
            ->willReturn(count($data));

        $this->em->expects(self::once())
            ->method('commit');

        $request = new Request();
        $request->setSession($this->createMock(Session::class));

        /** @var RedirectResponse $result */
        $result = $this->processor->process($data, $request);
        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertEquals($redirectUrl, $result->getTargetUrl());
    }

    public function testProcessWhenCommittedAndSameProductsCoupleOfTimes(): void
    {
        $data = $this->getProductData([
            ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'item'],
            ['productSku' => 'sku1Абв', 'productQuantity' => 3, 'productUnit' => 'kg'],
            ['productSku' => 'sku1абВ', 'productQuantity' => 4, 'productUnit' => 'set'],
            ['productSku' => 'sku1абв', 'productQuantity' => 5, 'productUnit' => 'item'],
            ['productSku' => 'sku2', 'productQuantity' => 5, 'productUnit' => 'item'],
            ['productSku' => 'sku2', 'productQuantity' => 6, 'productUnit' => 'kg'],
        ]);

        $productMap = [1, 1, 1, 1, 2, 2];
        $productIds = [1, 2];
        $productUnitsQuantities = [1 => ['item' => 7, 'kg' => 3, 'set' => 4], 2 => ['item' => 5, 'kg' => 6]];

        $this->shoppingListLimitManager->expects(self::once())
            ->method('isReachedLimit')
            ->willReturn(false);

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->em->expects(self::once())
            ->method('persist');
        $this->em->expects(self::once())
            ->method('flush');

        $this->dateFormatter->expects(self::once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $redirectUrl = '/customer/shoppingList/123';
        $startResult = [
            'shoppingList' => $shoppingList,
            'redirectUrl' => $redirectUrl
        ];

        $this->expectsMapProducts($productMap);

        $this->startQuickOrderCheckout->expects(self::once())
            ->method('execute')
            ->with($shoppingList, 'start_transition')
            ->willReturn($startResult);

        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('createForShoppingList')
            ->with(self::isInstanceOf(ShoppingList::class), $productIds, $productUnitsQuantities)
            ->willReturn(count($data));

        $this->em->expects(self::once())
            ->method('commit');

        $request = new Request();
        $request->setSession($this->createMock(Session::class));

        /** @var RedirectResponse $result */
        $result = $this->processor->process($data, $request);
        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertEquals($redirectUrl, $result->getTargetUrl());
    }

    public function testProcessWhenCommittedWithLimit(): void
    {
        $data = $this->getProductData([
            ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
            ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
        ]);

        $productMap = [1, 2];
        $productIds = [1, 2];
        $productUnitsQuantities = [1 => ['kg' => 2], 2 => ['liter' => 3]];

        $this->shoppingListLimitManager->expects(self::once())
            ->method('isReachedLimit')
            ->willReturn(true);

        $shoppingList = new ShoppingList();

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $this->currentShoppingListManager->expects(self::once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->shoppingListManager->expects(self::once())
            ->method('edit')
            ->willReturn($shoppingList);

        $this->shoppingListManager->expects(self::once())
            ->method('removeLineItems');

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');

        $this->expectsMapProducts($productMap);

        $redirectUrl = 'some/url';
        $startResult = [
            'shoppingList' => $shoppingList,
            'redirectUrl' => $redirectUrl
        ];

        $this->startQuickOrderCheckout->expects(self::once())
            ->method('execute')
            ->with($shoppingList, 'start_transition')
            ->willReturn($startResult);

        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('createForShoppingList')
            ->with(self::isInstanceOf(ShoppingList::class), $productIds, $productUnitsQuantities)
            ->willReturn(count($data));

        $request = new Request();
        $request->setSession($this->createMock(Session::class));

        $result = $this->processor->process($data, $request);
        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertEquals($redirectUrl, $result->getTargetUrl());
    }

    public function testProcessWhenActionGroupFailedWithErrors(): void
    {
        $data = $this->getProductData([
            ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
            ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
        ]);

        $productMap = [1, 2];
        $productIds = [1, 2];
        $productUnitsQuantities = [1 => ['kg' => 2], 2 => ['liter' => 3]];

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->dateFormatter->expects(self::once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $startResult = [
            'shoppingList' => $shoppingList,
            'redirectUrl' => null,
            'errors' => []
        ];

        $this->expectsMapProducts($productMap);

        $this->startQuickOrderCheckout->expects(self::once())
            ->method('execute')
            ->with($shoppingList, 'start_transition')
            ->willReturn($startResult);

        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('createForShoppingList')
            ->with(self::isInstanceOf(ShoppingList::class), $productIds, $productUnitsQuantities)
            ->willReturn(count($data));

        $request = new Request();
        $this->expectsFailedFlashMessage($request);

        $this->em->expects(self::once())
            ->method('rollback');

        self::assertNull($this->processor->process($data, $request));
    }

    public function testProcessWhenHandlerThrowsException(): void
    {
        $data = $this->getProductData([
            ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
            ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
        ]);

        $productMap = [1, 2];

        $shoppingList = new ShoppingList();
        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->dateFormatter->expects(self::once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $this->expectsMapProducts($productMap);

        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('createForShoppingList')
            ->willThrowException(new AccessDeniedException());

        $request = new Request();
        $this->expectsFailedFlashMessage($request);

        $this->em->expects(self::once())
            ->method('rollback');

        self::assertNull($this->processor->process($data, $request));
    }

    public function testProcessWhenNoItemsCreatedForShoppingList(): void
    {
        $data = $this->getProductData([
            ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
            ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
        ]);

        $productMap = [1, 2];
        $productIds = [1, 2];
        $productUnitsQuantities = [1 => ['kg' => 2], 2 => ['liter' => 3]];

        $shoppingList = new ShoppingList();

        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->willReturn($shoppingList);

        $this->dateFormatter->expects(self::once())
            ->method('format')
            ->willReturn('Mar 28, 2016, 2:50 PM');

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Quick Order (Mar 28, 2016, 2:50 PM)');

        $this->expectsMapProducts($productMap);

        $this->shoppingListLineItemHandler->expects(self::once())
            ->method('createForShoppingList')
            ->with(self::isInstanceOf(ShoppingList::class), $productIds, $productUnitsQuantities)
            ->willReturn(0);

        $this->em->expects(self::once())
            ->method('rollback');

        $request = new Request();
        $this->expectsFailedFlashMessage($request);

        self::assertNull($this->processor->process($data, $request));
    }

    public function testNotAllowedWhenEntityCreationNotAllowed(): void
    {
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:commerce@Oro\Bundle\CheckoutBundle\Entity\Checkout')
            ->willReturn(false);


        self::assertFalse($this->processor->isAllowed());
    }

    public function testNotAllowedWhenShoppingListNotAllowed(): void
    {
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturn(true);

        $this->isWorkflowStartFromShoppingListAllowed->expects(self::once())
            ->method('isAllowedForAny')
            ->willReturn(false);

        self::assertFalse($this->processor->isAllowed());
    }

    private function expectsMapProducts(array $productMap): void
    {
        $this->productMapper->expects(self::once())
            ->method('mapProducts')
            ->willReturnCallback(function (ArrayCollection $collection) use ($productMap) {
                foreach ($productMap as $i => $productId) {
                    if (null !== $productId) {
                        $item = $collection[$i];
                        self::assertInstanceOf(\ArrayAccess::class, $item);
                        $item['productId'] = $productId;
                    }
                }
            });
    }

    private function expectsFailedFlashMessage(Request $request)
    {
        $message = 'failed message';

        $this->messageGenerator->expects(self::once())
            ->method('getFailedMessage')
            ->willReturn($message);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('error');

        $session = $this->createMock(Session::class);
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $request->setSession($session);
    }

    private function getProductData(array $data): array
    {
        return [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $data,
            ProductDataStorage::TRANSITION_NAME_KEY => 'start_transition'
        ];
    }
}
