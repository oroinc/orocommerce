<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
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

    protected function setUp()
    {
        $this->formFactory = $this->getMock(FormFactoryInterface::class);
        $this->provider = new ProductFormProvider($this->formFactory);
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
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddForm();
        $this->assertInstanceOf(FormAccessor::class, $data);
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
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteForm();
        $this->assertInstanceOf(FormAccessor::class, $data);
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
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddImportForm();
        $this->assertInstanceOf(FormAccessor::class, $data);
    }

    public function testGetLineItemForm()
    {
        $expectedForm = $this->getMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getLineItemForm();
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getLineItemForm();
        $this->assertInstanceOf(FormAccessor::class, $data);
    }

    public function testGetLineItemFormWithInstanceName()
    {
        $expectedForm = $this->getMock(FormInterface::class);

        $this->formFactory->expects($this->exactly(2))
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getLineItemForm(null, 'form1');
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getLineItemForm(null, 'form1');
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get new form instance
        $data = $this->provider->getLineItemForm(null, 'form2');
        $this->assertInstanceOf(FormAccessor::class, $data);
    }

    public function testGetLineItemFormWithProduct()
    {
        $expectedForm = $this->getMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        $product = $this->getMock(Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        // Get form without existing data in locale cache
        $data = $this->provider->getLineItemForm($product);
        $this->assertInstanceOf(FormAccessor::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getLineItemForm($product);
        $this->assertInstanceOf(FormAccessor::class, $data);
    }
}
