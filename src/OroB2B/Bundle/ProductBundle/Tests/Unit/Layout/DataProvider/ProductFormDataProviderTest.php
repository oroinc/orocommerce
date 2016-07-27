<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\ProductFormDataProvider;

class ProductFormDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductFormDataProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    protected function setUp()
    {
        $this->formFactory = $this->getMock(FormFactoryInterface::class);
        $this->provider = new ProductFormDataProvider($this->formFactory);
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
