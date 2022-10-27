<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

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
 */
class QuickAddHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const PRODUCT_CLASS = Product::class;

    private const COMPONENT_NAME = 'component';

    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductFormProvider */
    private $productFormProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuickAddRowCollectionBuilder */
    private $quickAddRowCollectionBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ComponentProcessorRegistry */
    private $componentRegistry;

    /** @var QuickAddHandler */
    private $handler;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    protected function setUp(): void
    {
        $this->productFormProvider = $this->createMock(ProductFormProvider::class);
        $this->quickAddRowCollectionBuilder = $this->createMock(QuickAddRowCollectionBuilder::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->componentRegistry = $this->createMock(ComponentProcessorRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($message) {
                return $message . '.trans';
            });

        $this->handler = new QuickAddHandler(
            $this->productFormProvider,
            $this->quickAddRowCollectionBuilder,
            $this->componentRegistry,
            $this->router,
            $translator,
            $this->validator,
            new ProductsGrouperFactory(),
            $this->eventDispatcher
        );
    }

    public function testProcessGetRequest()
    {
        $request = Request::create('/get');

        $this->assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessNoProcessor()
    {
        $request = Request::create('/post/no-processor', 'POST');
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddForm')
            ->with([], ['products' => [], 'validation_groups' => ['Default', 'not_request_for_quote']])
            ->willReturn($form);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $this->assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessNotAllowedProcessor()
    {
        $request = Request::create('/post/not-allowed-processor', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddForm')
            ->with([], [
                'validation_required' => false,
                'products' => [],
                'validation_groups' => ['Default', 'not_request_for_quote']])
            ->willReturn($form);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor(false, false);
        $processor->expects($this->never())
            ->method('process');

        $this->assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessInvalidForm()
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
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->productFormProvider->expects($this->once())
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
        $collection->expects($this->once())
            ->method('getProducts')
            ->willReturn(['SKU1' => $product]);
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor();
        $processor->expects($this->never())
            ->method('process');

        $this->assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessValidDataWithoutResponse()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $request->request->set(QuickAddType::NAME, [
            QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME,
            QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform'
        ]);

        $productRows = [
            $this->createProductRow('111', 123, 'kg'),
            $this->createProductRow('222', 234, 'liter')
        ];
        $products = [
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '111',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 123,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'kg'
            ],
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '222',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 234,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'liter'
            ]
        ];

        $productsForm = $this->createMock(FormInterface::class);
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($productRows);

        $mainForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $mainForm->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $mainForm->expects($this->once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->productFormProvider->expects($this->once())
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
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor();
        $processor->expects($this->once())
            ->method('process')
            ->with(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => null,
                    ProductDataStorage::TRANSITION_NAME_KEY => 'start_from_quickorderform',
                ],
                $request
            );

        $this->router->expects($this->once())->method('generate')->with('reload')->willReturn('/reload');

        /** @var RedirectResponse $response */
        $response = $this->handler->process($request, 'reload');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/reload', $response->getTargetUrl());
    }

    public function testProcessValidDataWithResponse()
    {
        $request = Request::create('/post/valid-with-response', 'POST');
        $request->request->set(QuickAddType::NAME, [
            QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME,
            QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform'
        ]);

        $response = new RedirectResponse('/processor-redirect');

        $productRows = [
            $this->createProductRow('111', 123, 'kg'),
            $this->createProductRow('222', 234, 'liter')
        ];

        $products = [
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '111',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 123,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'kg'
            ],
            [
                ProductDataStorage::PRODUCT_SKU_KEY => '222',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => 234,
                ProductDataStorage::PRODUCT_UNIT_KEY => 'liter'
            ]
        ];

        $productsForm = $this->createMock(FormInterface::class);
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($productRows);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->productFormProvider->expects($this->once())
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
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($request)
            ->willReturn($collection);

        $processor = $this->getProcessor();
        $processor->expects($this->once())
            ->method('process')
            ->with(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => null,
                    ProductDataStorage::TRANSITION_NAME_KEY => 'start_from_quickorderform'
                ],
                $request
            )
            ->willReturn($response);

        $this->assertEquals($response->getTargetUrl(), $this->handler->process($request, 'reload')->getTargetUrl());
    }

    public function testProcessImport()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddForm')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->getMockForAbstractClass(FormInterface::class);
        $file = $this->getMockBuilder(UploadedFile::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([tempnam($this->getTempDir('quick_add_handler'), ''), 'dummy'])
            ->getMock();
        $form->expects($this->once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm);
        $fileForm->expects($this->once())
            ->method('getData')
            ->willReturn($file);

        $collection = new QuickAddRowCollection();
        $collection->add(new QuickAddRow('idx', 'psku1', 1));
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromFile')
            ->with($file)
            ->willReturn($collection);

        $actual = $this->handler->processImport($request);
        $this->assertEquals($collection, $actual);
    }

    public function testProcessImportNotValid()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $form->expects($this->never())
            ->method('get')
            ->with('file');
        $this->handler->processImport($request);
    }

    public function testProcessCopyPaste()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddCopyPasteForm')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $text = 'SKU1, 1';
        $copyPasteForm = $this->getMockForAbstractClass(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('copyPaste')
            ->willReturn($copyPasteForm);
        $copyPasteForm->expects($this->once())
            ->method('getData')
            ->willReturn($text);

        $collection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder->expects($this->once())
            ->method('buildFromCopyPasteText')
            ->with($text)
            ->willReturn($collection);
        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddForm')
            ->willReturn($form);

        $actual = $this->handler->processCopyPaste($request);
        $this->assertEquals($collection, $actual);
    }

    public function testProcessCopyPasteNotValid()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass(FormInterface::class);

        $this->productFormProvider->expects($this->once())
            ->method('getQuickAddCopyPasteForm')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->handler->processCopyPaste($request);
        $this->assertNull($result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Session
     */
    private function getSessionWithErrorMessage()
    {
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'oro.product.frontend.quick_add.messages.component_not_accessible.trans');

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        return $session;
    }

    /**
     * @param bool $isValidationRequired
     * @param bool $isAllowed
     * @return \PHPUnit\Framework\MockObject\MockObject|ComponentProcessorInterface
     */
    private function getProcessor($isValidationRequired = true, $isAllowed = true)
    {
        $processor = $this->createMock(ComponentProcessorInterface::class);
        $processor->expects($this->any())
            ->method('isValidationRequired')
            ->willReturn($isValidationRequired);
        $processor->expects($this->any())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        $this->componentRegistry->expects($this->once())
            ->method('getProcessorByName')
            ->with(self::COMPONENT_NAME)
            ->willReturn($processor);

        return $processor;
    }

    /**
     * @param string $sku
     * @param string $qty
     * @param string $unit
     * @return ProductRow
     */
    private function createProductRow($sku, $qty, $unit)
    {
        $row = new ProductRow();
        $row->productSku = $sku;
        $row->productQuantity = $qty;
        $row->productUnit = $unit;

        return $row;
    }
}
