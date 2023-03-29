<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuickAddCheckoutProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLineItemHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var MessageGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $messageGenerator;

    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLimitManager;

    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var ActionGroupRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $actionGroupRegistry;

    /** @var ActionGroup|\PHPUnit\Framework\MockObject\MockObject */
    private $actionGroup;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateFormatter;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var QuickAddCheckoutProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(ShoppingListLineItemHandler::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->messageGenerator = $this->createMock(MessageGenerator::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $this->actionGroup = $this->createMock(ActionGroup::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->dateFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->processor = new QuickAddCheckoutProcessor(
            $this->handler,
            $doctrine,
            $this->aclHelper,
            $this->messageGenerator,
            $this->shoppingListManager,
            $this->shoppingListLimitManager,
            $this->currentShoppingListManager,
            $this->actionGroupRegistry,
            $this->translator,
            $this->dateFormatter,
            'start_shoppinglist_checkout'
        );
    }

    public function testGetName(): void
    {
        self::assertEquals('oro_shopping_list_to_checkout_quick_add_processor', $this->processor->getName());
    }

    public function testIsValidationRequired(): void
    {
        self::assertTrue($this->processor->isValidationRequired());
    }

    public function testIsAllowed(): void
    {
        $this->handler->expects(self::once())
            ->method('isAllowed')
            ->willReturn(true);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        self::assertTrue($this->processor->isAllowed());
    }

    public function testIsAllowedAndNoActionGroup(): void
    {
        $this->handler->expects(self::once())
            ->method('isAllowed')
            ->willReturn(true);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn(null);

        self::assertFalse($this->processor->isAllowed());
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
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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
        $actionData = new ActionData([
            'shoppingList' => $shoppingList,
            'redirectUrl' => $redirectUrl
        ]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects(self::once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        $this->actionGroup->expects(self::once())
            ->method('execute')
            ->willReturn($actionData);

        $this->handler->expects(self::once())
            ->method('createForShoppingList')
            ->with(
                self::isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
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
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects(self::once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $redirectUrl = 'some/url';
        $actionData = new ActionData([
            'shoppingList' => $shoppingList,
            'redirectUrl' => $redirectUrl
        ]);

        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        $this->actionGroup->expects(self::once())
            ->method('execute')
            ->willReturn($actionData);

        $this->handler->expects(self::once())
            ->method('createForShoppingList')
            ->with(
                self::isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(count($data));

        $request = new Request();
        $request->setSession($this->createMock(Session::class));

        $result = $this->processor->process($data, $request);
        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertEquals($redirectUrl, $result->getTargetUrl());
    }

    public function testProcessWhenActionGroupFailedWithErrors(): void
    {
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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

        $actionData = new ActionData([
            'shoppingList' => $shoppingList,
            'redirectUrl' => null,
            'errors' => []
        ]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects(self::once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($this->actionGroup);

        $this->actionGroup->expects(self::once())
            ->method('execute')
            ->willReturn($actionData);

        $this->handler->expects(self::once())
            ->method('createForShoppingList')
            ->with(
                self::isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(count($data));

        $request = new Request();
        $this->expectsFailedFlashMessage($request);

        $this->em->expects(self::once())
            ->method('rollback');

        self::assertNull($this->processor->process($data, $request));
    }

    public function testProcessWhenHandlerThrowsException(): void
    {
        $data = $this->getProductData();

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

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects(self::once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->handler->expects(self::once())
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
        $data = $this->getProductData();

        $productIds = ['sku1абв' => 1, 'sku2' => 2];
        $productUnitsQuantities = ['SKU1АБВ' => ['kg' => 2], 'SKU2' => ['liter' => 3]];

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

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 1, 'sku' => 'SKU1АБВ'],
                ['id' => 2, 'sku' => 'SKU2'],
            ]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects(self::once())
            ->method('getProductsIdsBySkuQueryBuilder')
            ->with(['sku1абв', 'sku2'])
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->handler->expects(self::once())
            ->method('createForShoppingList')
            ->with(
                self::isInstanceOf(ShoppingList::class),
                array_values($productIds),
                $productUnitsQuantities
            )
            ->willReturn(0);

        $this->em->expects(self::once())
            ->method('rollback');

        $request = new Request();
        $this->expectsFailedFlashMessage($request);

        self::assertNull($this->processor->process($data, $request));
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
            ->with('error')
            ->willReturn($flashBag);

        $session = $this->createMock(Session::class);
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $request->setSession($session);
    }

    private function getProductData(): array
    {
        return [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => 'sku1абв', 'productQuantity' => 2, 'productUnit' => 'kg'],
                ['productSku' => 'sku2', 'productQuantity' => 3, 'productUnit' => 'liter'],
            ]
        ];
    }
}
