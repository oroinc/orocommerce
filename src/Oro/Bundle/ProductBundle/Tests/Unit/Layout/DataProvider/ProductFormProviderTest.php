<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductFormProviderTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_VARIANTS_GET_AVAILABLE_VARIANTS = 'product_variants_get_available_variants_url';

    use EntityTrait;

    /** @var ProductFormProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $productVariantAvailabilityProvider;

    protected function setUp(): void
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
            ->with(QuickAddType::class)
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
            ->with(QuickAddType::class)
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
            ->with(QuickAddCopyPasteType::class)
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
            ->with(QuickAddCopyPasteType::class)
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
            ->with(QuickAddImportFromFileType::class)
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
            ->with(QuickAddImportFromFileType::class)
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
            ->with(FrontendLineItemType::class)
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
            ->with(FrontendLineItemType::class)
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
            ->with(FrontendLineItemType::class)
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

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1001]);
        /** @var Product $productVariant */
        $productVariant = $this->getEntity(Product::class, ['id' => 2002]);

        $this->productVariantAvailabilityProvider->expects($this->atLeastOnce())
            ->method('getSimpleProductByVariantFields')
            ->with($product, [], false)
            ->willReturn($productVariant);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                FrontendVariantFiledType::class,
                $productVariant,
                $this->getProductVariantExpectedOptions($product, 2)
            )
            ->willReturn($form);

        $data = $this->provider->getVariantFieldsFormView($product);
        $this->assertSame($formView, $data);

        //check local cache
        $data = $this->provider->getVariantFieldsFormView($product);
        $this->assertSame($formView, $data);
    }

    public function testGetVariantFieldsFormViewByVariantProduct()
    {
        $formView = $this->createMock(FormView::class);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1001]);

        /** @var Product $variantProduct */
        $variantProduct = $this->getEntity(Product::class, ['id' => 1003]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                FrontendVariantFiledType::class,
                $variantProduct,
                $this->getProductVariantExpectedOptions($product, 2)
            )
            ->willReturn($form);

        $data = $this->provider->getVariantFieldsFormViewByVariantProduct($product, $variantProduct);
        $this->assertSame($formView, $data);

        //check local cache
        $data = $this->provider->getVariantFieldsFormViewByVariantProduct($product, $variantProduct);
        $this->assertSame($formView, $data);
    }

    public function testGetVariantFieldsForm()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1001]);
        /** @var Product $productVariant */
        $productVariant = $this->getEntity(Product::class, ['id' => 2002]);

        $this->productVariantAvailabilityProvider->expects($this->atLeastOnce())
            ->method('getSimpleProductByVariantFields')
            ->with($product, [], false)
            ->willReturn($productVariant);

        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                FrontendVariantFiledType::class,
                $productVariant,
                $this->getProductVariantExpectedOptions($product, 2)
            )
            ->willReturn($form);

        $data = $this->provider->getVariantFieldsForm($product);
        $this->assertSame($form, $data);

        //check local cache
        $data = $this->provider->getVariantFieldsForm($product);
        $this->assertSame($form, $data);
    }

    /**
     * @param Product $product
     * @param int $expects
     * @return array
     */
    private function getProductVariantExpectedOptions(Product $product, $expects = 1)
    {
        $this->router->expects($this->exactly($expects))
            ->method('generate')
            ->with('oro_product_frontend_ajax_product_variant_get_available', ['id' => $product->getId()])
            ->willReturn('product_variants_get_available_variants_url');

        return [
            'action' => 'product_variants_get_available_variants_url',
            'parentProduct' => $product,
            'dynamic_fields_disabled' => true
        ];
    }
}
