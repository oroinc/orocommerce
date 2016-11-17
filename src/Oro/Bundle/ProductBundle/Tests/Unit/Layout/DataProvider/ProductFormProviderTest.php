<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;

class ProductFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductFormProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    protected function setUp()
    {
        $this->formFactory = $this->getMock(FormFactoryInterface::class);
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->provider = new ProductFormProvider($this->formFactory, $this->router);
    }

    public function testGetQuickAddFormView()
    {
        $formView = $this->getMock(FormView::class);

        $expectedForm = $this->getMock(FormInterface::class);
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddFormView();
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddFormView();
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetQuickAddForm()
    {
        $expectedForm = $this->getMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetQuickAddCopyPasteFormView()
    {
        $formView = $this->getMock(FormView::class);

        $expectedForm = $this->getMock(FormInterface::class);
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddCopyPasteType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteFormView();
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteFormView();
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetQuickAddCopyPasteForm()
    {
        $expectedForm = $this->getMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddCopyPasteType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetQuickAddImportFormView()
    {
        $formView = $this->getMock(FormView::class);

        $expectedForm = $this->getMock(FormInterface::class);
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddImportFromFileType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddImportFormView();
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddImportFormView();
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetQuickAddImportForm()
    {
        $expectedForm = $this->getMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddImportFromFileType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddImportForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddImportForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetLineItemFormView()
    {
        $formView = $this->getMock(FormView::class);

        $expectedForm = $this->getMock(FormInterface::class);
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getLineItemFormView();
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getLineItemFormView();
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetLineItemFormViewWithInstanceName()
    {
        $formView = $this->getMock(FormView::class);

        $expectedForm = $this->getMock(FormInterface::class);
        $expectedForm->expects($this->exactly(2))
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->exactly(2))
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data1 = $this->provider->getLineItemFormView(null, 'form1');

        // Get form with existing data in locale cache
        $data1Cache = $this->provider->getLineItemFormView(null, 'form1');
        $this->assertSame($data1, $data1Cache);

        // Get new form instance
        $data2 = $this->provider->getLineItemFormView(null, 'form2');
        $this->assertSame($data1, $data2);
        $this->assertEquals($data1, $data2);
    }

    public function testGetLineItemFormViewWithProduct()
    {
        $formView = $this->getMock(FormView::class);

        $expectedForm = $this->getMock(FormInterface::class);
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        $product = $this->getMock(Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        // Get form without existing data in locale cache
        $data = $this->provider->getLineItemFormView($product);
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getLineItemFormView($product);
        $this->assertInstanceOf(FormView::class, $data);
    }
}
