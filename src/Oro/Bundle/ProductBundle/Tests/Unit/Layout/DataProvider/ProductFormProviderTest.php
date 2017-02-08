<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductFormProviderTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_VARIANTS_GET_AVAILABLE_VARIANTS = 'product_variants_get_available_variants_url';

    /** @var ProductFormProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $productVariantAvailabilityProvider;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->productVariantAvailabilityProvider = $this->getMockBuilder(ProductVariantAvailabilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ProductFormProvider(
            $this->formFactory,
            $this->router,
            $this->productVariantAvailabilityProvider
        );
    }

    public function testGetQuickAddFormView()
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
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
        $expectedForm = $this->createMock(FormInterface::class);

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
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
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
        $expectedForm = $this->createMock(FormInterface::class);

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
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
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
        $expectedForm = $this->createMock(FormInterface::class);

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
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
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
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
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
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendLineItemType::NAME)
            ->willReturn($expectedForm);

        $product = $this->createMock(Product::class);
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

    public function testGetVariantFieldsFormView()
    {
        $formView = $this->createMock(FormView::class);

        $product = new Product();
        $productVariant = new Product();

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductByVariantFields')
            ->with($product, [], false)
            ->willReturn($productVariant);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendVariantFiledType::NAME, $productVariant, $this->getProductVariantExpectedOptions($product))
            ->willReturn($form);

        $data = $this->provider->getVariantFieldsFormView($product);
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetVariantFieldsForm()
    {
        $product = new Product();
        $productVariant = new Product();

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductByVariantFields')
            ->with($product, [], false)
            ->willReturn($productVariant);

        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendVariantFiledType::NAME, $productVariant, $this->getProductVariantExpectedOptions($product))
            ->willReturn($form);

        $data = $this->provider->getVariantFieldsForm($product);
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getProductVariantExpectedOptions(Product $product)
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_product_frontend_ajax_product_variant_get_available', ['id' => $product->getId()])
            ->willReturn('product_variants_get_available_variants_url');

        return [
            'action' => 'product_variants_get_available_variants_url',
            'parentProduct' => $product,
        ];
    }

    public function testGetSimpleProductVariants()
    {
        $product = new Product();
        $product->setVariantFields([ 'field_first', 'field_second']);
        $productVariant = new Product();

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$productVariant]);

        $this->productVariantAvailabilityProvider->expects($this->exactly(2))
            ->method('getVariantFieldScalarValue')
            ->withConsecutive(
                [$productVariant, 'field_first'],
                [$productVariant, 'field_second']
            )
            ->willReturnOnConsecutiveCalls('value1', 'value2');

        $expectedResult = [
            null => [
                'field_first' => 'value1',
                'field_second' => 'value2',
            ]
        ];

        $this->assertEquals($expectedResult, $this->provider->getSimpleProductVariants($product));
    }
}
