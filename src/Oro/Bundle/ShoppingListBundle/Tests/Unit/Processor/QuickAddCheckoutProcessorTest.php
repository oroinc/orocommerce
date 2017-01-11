<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddCheckoutProcessorTest extends AbstractQuickAddProcessorTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager
     */
    protected $shoppingListManager;

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
     * @param array $data
     * @param Request $request
     * @param array $productIds
     * @param array $productQuantities
     * @param bool $isSetRedirectUrl
     * @param bool $failed
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $data,
        Request $request,
        array $productIds = [],
        array $productQuantities = [],
        $isSetRedirectUrl = true,
        $failed = false
    ) {

        if (!empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])) {
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
                'redirectUrl' => $isSetRedirectUrl ? '/customer/shoppingList/123' : null,
            ]);

            $entitiesCount = count($data);

            $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

            if ($failed) {
                $this->handler->expects($this->once())
                    ->method('createForShoppingList')
                    ->willThrowException(new AccessDeniedException());

                $this->assertFailedFlashMessage($request);
            } else {
                if (!$isSetRedirectUrl) {
                    $this->messageGenerator->expects($this->once())
                        ->method('getFailedMessage')
                        ->willReturn('error');

                    $this->assertFailedFlashMessage($request);
                }

                $this->actionGroupRegistry->expects($this->once())
                    ->method('findByName')
                    ->with('start_shoppinglist_checkout')
                    ->willReturn($this->actionGroup);

                $this->actionGroup->expects($this->once())
                    ->method('execute')
                    ->willReturn($actionData);

                $this->handler->expects($data ? $this->once() : $this->never())
                    ->method('createForShoppingList')
                    ->with(
                        $this->isInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList'),
                        array_values($productIds),
                        $productQuantities
                    )
                    ->willReturn($entitiesCount);
            }
        }

        $this->processor->process($data, $request);
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
    public function processDataProvider()
    {
        return [
            'empty' => [
                'data' => [],
                'request' => new Request()
            ],
            'not empty' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                    ]
                ],
                'request' => new Request(),
                'productIds' =>['sku1' => 1, 'sku2' => 2],
                'productQuantities' => [1 => 2, 2 => 3],
            ],
            'process failed' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                    ]
                ],
                'request' => new Request(),
                'productIds' =>['sku1' => 1, 'sku2' => 2],
                'productQuantities' => [1 => 2, 2 => 3],
                'redirectUrl' => '/customer/shoppingList/123',
                'failed' => true
            ],
            'without redirect url' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                    ]
                ],
                'request' => new Request(),
                'productIds' =>['sku1' => 1, 'sku2' => 2],
                'productQuantities' => [1 => 2, 2 => 3],
                'failed' => false,
                'issetRedirectUrl' => false
            ],
        ];
    }
}
