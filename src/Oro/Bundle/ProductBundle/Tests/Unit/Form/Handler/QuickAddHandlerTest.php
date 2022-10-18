<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductsGrouperFactory;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuickAddHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const PRODUCT_CLASS = Product::class;

    private const COMPONENT_NAME = 'component';

    private UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $router;

    private ProductFormProvider|\PHPUnit\Framework\MockObject\MockObject $productFormProvider;

    private QuickAddRowCollectionBuilder|\PHPUnit\Framework\MockObject\MockObject $quickAddRowCollectionBuilder;

    private ComponentProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject $componentRegistry;

    private QuickAddHandler $handler;

    protected function setUp(): void
    {
        $this->productFormProvider = $this->createMock(ProductFormProvider::class);
        $this->quickAddRowCollectionBuilder = $this->createMock(QuickAddRowCollectionBuilder::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->componentRegistry = $this->createMock(ComponentProcessorRegistry::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $message) => $message . '.trans');

        $this->handler = new QuickAddHandler(
            $this->productFormProvider,
            $this->quickAddRowCollectionBuilder,
            $this->componentRegistry,
            $this->router,
            $translator,
            $validator,
            new ProductsGrouperFactory(),
            $eventDispatcher
        );
    }

    public function testProcessGetRequest(): void
    {
        $request = Request::create('/get');

        self::assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessNoProcessor(): void
    {
        $request = Request::create('/post/no-processor', 'POST');
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->with([], ['products' => [], 'validation_groups' => ['Default', 'not_request_for_quote']])
            ->willReturn($form);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        self::assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessNotAllowedProcessor(): void
    {
        $request = Request::create('/post/not-allowed-processor', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->with([], [
                'validation_required' => false,
                'products' => [],
                'validation_groups' => ['Default', 'not_request_for_quote'],
            ])
            ->willReturn($form);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor(false, false);
        $processor->expects(self::never())
            ->method('process');

        self::assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessInvalidForm(): void
    {
        $request = Request::create('/post/invalid-form', 'POST');
        $request->request->set(
            QuickAddType::NAME,
            [
                QuickAddType::PRODUCTS_FIELD_NAME => [
                    [ProductDataStorage::PRODUCT_SKU_KEY => 'sku1'],
                    [ProductDataStorage::PRODUCT_SKU_KEY => 'sku2'],
                ],
                QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME,
            ]
        );

        $product = new Product();
        $product->setSku('SKU1');

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->with(
                [],
                [
                    'validation_required' => true,
                    'products' => ['SKU1' => $product],
                    'validation_groups' => ['Default', 'not_request_for_quote'],
                ]
            )
            ->willReturn($form);

        $collection = $this->createMock(QuickAddRowCollection::class);
        $collection->expects(self::once())
            ->method('getProducts')
            ->willReturn(['SKU1' => $product]);
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor();
        $processor->expects(self::never())
            ->method('process');

        self::assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessValidDataWithoutResponse(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $request->request->set(QuickAddType::NAME, [
            QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME,
            QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
        ]);

        $productRows = [
            $this->createProductRow('111', 123, 'kg'),
            $this->createProductRow('222', 234, 'liter'),
        ];
        $products = [
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '111',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 123,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'kg',
            ],
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '222',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 234,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'liter',
            ],
        ];

        $productsForm = $this->createMock(FormInterface::class);
        $productsForm->expects(self::once())
            ->method('getData')
            ->willReturn($productRows);

        $mainForm = $this->createMock(FormInterface::class);
        $mainForm->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $mainForm->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $mainForm->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $mainForm->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->with(
                [],
                [
                    'validation_required' => true,
                    'products' => [],
                    'validation_groups' => ['Default', 'not_request_for_quote'],
                ]
            )
            ->willReturn($mainForm);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor();
        $processor->expects(self::once())
            ->method('process')
            ->with(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => null,
                    ProductDataStorage::TRANSITION_NAME_KEY => 'start_from_quickorderform',
                ],
                $request
            );

        $this->router->expects(self::once())->method('generate')->with('reload')->willReturn('/reload');

        /** @var RedirectResponse $response */
        $response = $this->handler->process($request, 'reload');
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/reload', $response->getTargetUrl());
    }

    public function testProcessValidDataWithResponse(): void
    {
        $request = Request::create('/post/valid-with-response', 'POST');
        $request->request->set(QuickAddType::NAME, [
            QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME,
            QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
        ]);

        $response = new RedirectResponse('/processor-redirect');

        $productRows = [
            $this->createProductRow('111', 123, 'kg'),
            $this->createProductRow('222', 234, 'liter'),
        ];

        $products = [
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '111',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 123,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'kg',
            ],
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '222',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 234,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'liter',
            ],
        ];

        $productsForm = $this->createMock(FormInterface::class);
        $productsForm->expects(self::once())
            ->method('getData')
            ->willReturn($productRows);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->with(
                [],
                [
                    'validation_required' => true,
                    'products' => [],
                    'validation_groups' => ['Default', 'not_request_for_quote'],
                ]
            )
            ->willReturn($form);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor();
        $processor->expects(self::once())
            ->method('process')
            ->with(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => null,
                    ProductDataStorage::TRANSITION_NAME_KEY => 'start_from_quickorderform',
                ],
                $request
            )
            ->willReturn($response);

        self::assertEquals($response->getTargetUrl(), $this->handler->process($request, 'reload')->getTargetUrl());
    }

    public function testProcessImport(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->willReturn($form);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->getMockForAbstractClass(FormInterface::class);
        $file = $this->getMockBuilder(UploadedFile::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([tempnam($this->getTempDir('quick_add_handler'), ''), 'dummy'])
            ->getMock();
        $form->expects(self::once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm);
        $fileForm->expects(self::once())
            ->method('getData')
            ->willReturn($file);

        $collection = new QuickAddRowCollection();
        $collection->add(new QuickAddRow('idx', 'psku1', 1));
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromFile')
            ->with($file)
            ->willReturn($collection);

        $actual = $this->handler->processImport($request);
        self::assertEquals($collection, $actual);
    }

    public function testProcessImportWithPreloadingManager(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->willReturn($form);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->createMock(FormInterface::class);
        $file = $this->createMock(UploadedFile::class);
        $form->expects(self::once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm);
        $fileForm->expects(self::once())
            ->method('getData')
            ->willReturn($file);

        $collection = new QuickAddRowCollection();
        $collection->add(new QuickAddRow('idx', 'psku1', 1));
        $product1 = (new Product())->setSku('psku1');
        $collection->mapProducts(['PSKU1' => $product1]);

        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromFile')
            ->with($file)
            ->willReturn($collection);

        $preloadingManager = $this->createMock(PreloadingManager::class);
        $preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$product1], [
                'names' => [],
                'unitPrecisions' => [],
                'minimumQuantityToOrder' => [],
                'maximumQuantityToOrder' => [],
                'category' => ['minimumQuantityToOrder' => [], 'maximumQuantityToOrder' => []],
            ]);

        $this->handler->setPreloadingManager($preloadingManager);
        $actual = $this->handler->processImport($request);
        self::assertEquals($collection, $actual);
    }

    public function testProcessImportWithPreloadingManagerAndCustomConfig(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->willReturn($form);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->createMock(FormInterface::class);
        $file = $this->createMock(UploadedFile::class);
        $form->expects(self::once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm);
        $fileForm->expects(self::once())
            ->method('getData')
            ->willReturn($file);

        $collection = new QuickAddRowCollection();
        $collection->add(new QuickAddRow('idx', 'psku1', 1));
        $product1 = (new Product())->setSku('psku1');
        $collection->mapProducts(['PSKU1' => $product1]);

        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromFile')
            ->with($file)
            ->willReturn($collection);

        $preloadingManager = $this->createMock(PreloadingManager::class);
        $preloadingConfig = ['names' => []];
        $preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$product1], $preloadingConfig);

        $this->handler->setPreloadingManager($preloadingManager);
        $this->handler->setPreloadingConfig($preloadingConfig);
        $actual = $this->handler->processImport($request);
        self::assertEquals($collection, $actual);
    }

    public function testProcessImportNotValid(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $form->expects(self::never())
            ->method('get')
            ->with('file');
        $this->handler->processImport($request);
    }

    public function testProcessCopyPaste(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddCopyPasteForm')
            ->willReturn($form);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $text = 'SKU1, 1';
        $copyPasteForm = $this->getMockForAbstractClass(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('copyPaste')
            ->willReturn($copyPasteForm);
        $copyPasteForm->expects(self::once())
            ->method('getData')
            ->willReturn($text);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromCopyPasteText')
            ->with($text)
            ->willReturn($collection);
        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddForm')
            ->willReturn($form);

        $actual = $this->handler->processCopyPaste($request);
        self::assertEquals($collection, $actual);
    }

    public function testProcessCopyPasteNotValid(): void
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects(self::once())
            ->method('getQuickAddCopyPasteForm')
            ->willReturn($form);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->handler->processCopyPaste($request);
        self::assertNull($result);
    }

    private function getSessionWithErrorMessage(): Session|\PHPUnit\Framework\MockObject\MockObject
    {
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('error', 'oro.product.frontend.quick_add.messages.component_not_accessible.trans');

        $session = $this->createMock(Session::class);
        $session->expects(self::any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        return $session;
    }

    private function getProcessor(
        bool $isValidationRequired = true,
        bool $isAllowed = true
    ): ComponentProcessorInterface|\PHPUnit\Framework\MockObject\MockObject {
        $processor = $this->createMock(ComponentProcessorInterface::class);
        $processor->expects(self::any())
            ->method('isValidationRequired')
            ->willReturn($isValidationRequired);
        $processor->expects(self::any())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        $this->componentRegistry->expects(self::once())
            ->method('getProcessorByName')
            ->with(self::COMPONENT_NAME)
            ->willReturn($processor);

        return $processor;
    }

    private function createProductRow(string $sku, string $qty, string $unit): ProductRow
    {
        $row = new ProductRow();
        $row->productSku = $sku;
        $row->productQuantity = $qty;
        $row->productUnit = $unit;

        return $row;
    }
}
