<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddFormProvider;
use OroB2B\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddCopyPasteFormProvider;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddImportFormProvider;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandlerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    const COMPONENT_NAME = 'component';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuickAddFormProvider
     */
    protected $quickAddFormProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuickAddImportFormProvider
     */
    protected $quickAddImportFormProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuickAddCopyPasteFormProvider
     */
    protected $quickAddCopyPasteFormProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuickAddRowCollectionBuilder
     */
    protected $quickAddRowCollectionBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @var QuickAddHandler
     */
    protected $handler;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productRepository;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message.'.trans';
                }
            );

        $this->quickAddFormProvider = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddFormProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quickAddImportFormProvider = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddImportFormProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quickAddCopyPasteFormProvider = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddCopyPasteFormProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quickAddRowCollectionBuilder = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->componentRegistry = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new QuickAddHandler(
            $this->quickAddFormProvider,
            $this->quickAddImportFormProvider,
            $this->quickAddCopyPasteFormProvider,
            $this->quickAddRowCollectionBuilder,
            $this->componentRegistry,
            $this->router,
            $this->translator
        );
    }

    /**
     * @return ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProductRepository()
    {
        if (!$this->productRepository) {
            $this->productRepository = $this
                ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->productRepository;
    }

    public function testProcessGetRequest()
    {
        $request = Request::create('/get');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->quickAddFormProvider->expects($this->never())
            ->method('getForm')
            ->with([])
            ->willReturn($form);

        $this->assertEquals(null, $this->handler->process($request, 'reload'));
    }

    public function testProcessNoProcessor()
    {
        $request = Request::create('/post/no-processor', 'POST');
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $this->quickAddFormProvider->expects($this->once())
            ->method('getForm')
            ->with([], ['products' => []])
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

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $this->quickAddFormProvider->expects($this->once())
            ->method('getForm')
            ->with([], ['validation_required' => false, 'products' => []])
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
                    ['productSku' => 'sku1'],
                    ['productSku' => 'sku2'],
                ],
                QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME,
            ]
        );

        $product = new Product();
        $product->setSku('SKU1');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->quickAddFormProvider->expects($this->once())
            ->method('getForm')
            ->with([], ['validation_required' => true, 'products' => ['SKU1' => $product]])
            ->willReturn($form);

        $collection = $this->getMock('OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection');
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

        $this->assertEquals(null, $this->handler->process($request, 'reload', 'reload'));
    }

    public function testProcessValidDataWithoutResponse()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);

        $products = [['sku' => '111', 'qty' => 123], ['sku' => '222', 'qty' => 234]];

        $productsForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($products);

        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->once())
            ->method('submit')
            ->with($request);
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $mainForm->expects($this->once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->quickAddFormProvider->expects($this->at(0))
            ->method('getForm')
            ->with([], ['validation_required' => true, 'products' => []])
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
                    ProductDataStorage::ADDITIONAL_DATA_KEY => null
                ],
                $request
            );

        $this->router->expects($this->once())->method('generate')->with('reload')->willReturn('/reload');
        $this->assertEquals(new RedirectResponse('/reload'), $this->handler->process($request, 'reload'));
    }

    public function testProcessValidDataWithResponse()
    {
        $request = Request::create('/post/valid-with-response', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);

        $response = new RedirectResponse('/processor-redirect');

        $products = [['sku' => '111', 'qty' => 123], ['sku' => '222', 'qty' => 234]];

        $productsForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($products);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->quickAddFormProvider->expects($this->once())
            ->method('getForm')
            ->with([], ['validation_required' => true, 'products' => []])
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
                    ProductDataStorage::ADDITIONAL_DATA_KEY => null
                ],
                $request
            )
            ->willReturn($response);

        $this->assertEquals($response, $this->handler->process($request, 'reload'));
    }

    public function testProcessImport()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

        $this->quickAddImportFormProvider->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->enableOriginalConstructor()
            ->setConstructorArgs([tempnam(sys_get_temp_dir(), ''), 'dummy'])
            ->getMock();
        $form->expects($this->once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm);
        $fileForm->expects($this->once())
            ->method('getData')
            ->willReturn($file);

        $collection = new QuickAddRowCollection();
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
        $this->quickAddImportFormProvider->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
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

        $this->quickAddCopyPasteFormProvider->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
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
        $this->quickAddFormProvider->expects($this->once())
            ->method('getForm');

        $actula = $this->handler->processCopyPaste($request);
        $this->assertEquals($collection, $actula);
    }

    public function testProcessCopyPasteNotValid()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

        $this->quickAddCopyPasteFormProvider->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->handler->processCopyPaste($request);
        $this->assertNull($result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function getSessionWithErrorMessage()
    {
        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBag');
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'orob2b.product.frontend.quick_add.messages.component_not_accessible.trans');

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
     * @return \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorInterface
     */
    protected function getProcessor($isValidationRequired = true, $isAllowed = true)
    {
        $processor = $this->getMock('OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface');
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
}
