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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuickAddHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    const COMPONENT_NAME = 'component';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductFormProvider
     */
    protected $productFormProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|QuickAddRowCollectionBuilder
     */
    protected $quickAddRowCollectionBuilder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @var QuickAddHandler
     */
    protected $handler;

    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validator;

    /**
     * @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message.'.trans';
                }
            );

        $this->productFormProvider = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickAddRowCollectionBuilder = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->componentRegistry = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->handler = new QuickAddHandler(
            $this->productFormProvider,
            $this->quickAddRowCollectionBuilder,
            $this->componentRegistry,
            $this->router,
            $this->translator,
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

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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

        $collection = $this->createMock('Oro\Bundle\ProductBundle\Model\QuickAddRowCollection');
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

        $productsForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($productRows);

        $mainForm = $this->createMock('Symfony\Component\Form\FormInterface');
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

        $this->productFormProvider->expects($this->at(0))
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
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
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

        $productsForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($productRows);

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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
        $form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

        $this->productFormProvider->expects($this->at(0))
            ->method('getQuickAddImportForm')
            ->willReturn($form);

        $this->productFormProvider->expects($this->at(1))
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

        $fileForm = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
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
        $form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

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
        $form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

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
        $copyPasteForm = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');
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
        $form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

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
    protected function getSessionWithErrorMessage()
    {
        $flashBag = $this->createMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBag');
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'oro.product.frontend.quick_add.messages.component_not_accessible.trans');

        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
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
    protected function getProcessor($isValidationRequired = true, $isAllowed = true)
    {
        $processor = $this->createMock('Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface');
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
    protected function createProductRow($sku, $qty, $unit)
    {
        $row = new ProductRow();
        $row->productSku = $sku;
        $row->productQuantity = $qty;
        $row->productUnit = $unit;

        return $row;
    }
}
