<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductFormProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantAvailabilityProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductFormProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new ProductFormProvider(
            $this->formFactory,
            $this->router,
            $this->productVariantAvailabilityProvider,
            $this->doctrine
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
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

        $this->assertInstanceOf(FormView::class, $this->provider->getQuickAddFormView());
        // test memory cache
        $this->assertInstanceOf(FormView::class, $this->provider->getQuickAddFormView());
    }

    public function testGetQuickAddForm()
    {
        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::class)
            ->willReturn($expectedForm);

        $this->assertInstanceOf(FormInterface::class, $this->provider->getQuickAddForm());
        // test memory cache
        $this->assertInstanceOf(FormInterface::class, $this->provider->getQuickAddForm());
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

        $this->assertInstanceOf(FormView::class, $this->provider->getQuickAddCopyPasteFormView());
        // test memory cache
        $this->assertInstanceOf(FormView::class, $this->provider->getQuickAddCopyPasteFormView());
    }

    public function testGetQuickAddCopyPasteForm()
    {
        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddCopyPasteType::class)
            ->willReturn($expectedForm);

        $this->assertInstanceOf(FormInterface::class, $this->provider->getQuickAddCopyPasteForm());
        // test memory cache
        $this->assertInstanceOf(FormInterface::class, $this->provider->getQuickAddCopyPasteForm());
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

        $this->assertInstanceOf(FormView::class, $this->provider->getQuickAddImportFormView());
        // test memory cache
        $this->assertInstanceOf(FormView::class, $this->provider->getQuickAddImportFormView());
    }

    public function testGetQuickAddImportForm()
    {
        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddImportFromFileType::class)
            ->willReturn($expectedForm);

        $this->assertInstanceOf(FormInterface::class, $this->provider->getQuickAddImportForm());
        // test memory cache
        $this->assertInstanceOf(FormInterface::class, $this->provider->getQuickAddImportForm());
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

        $this->assertInstanceOf(FormView::class, $this->provider->getLineItemFormView(null));
        // test memory cache
        $this->assertInstanceOf(FormView::class, $this->provider->getLineItemFormView(null));
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

        $data1 = $this->provider->getLineItemFormView(null, 'form1');

        // test memory cache
        $this->assertSame($data1, $this->provider->getLineItemFormView(null, 'form1'));

        // get new form instance
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
            ->willReturn(1);

        $this->assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
        // test memory cache
        $this->assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
    }

    public function testGetLineItemFormViewWithProductView()
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

        $product = new ProductView();
        $product->set('id', 1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('getReference')
            ->with(Product::class, 1)
            ->willReturn($this->createMock(Product::class));

        $this->assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
        // test memory cache
        $this->assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
    }

    public function testGetVariantFieldsFormView()
    {
        $formView = $this->createMock(FormView::class);

        $product = $this->getProduct(1001);
        $productVariant = $this->getProduct(2002);

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

        $this->assertSame($formView, $this->provider->getVariantFieldsFormView($product));
        // test memory cache
        $this->assertSame($formView, $this->provider->getVariantFieldsFormView($product));
    }

    public function testGetVariantFieldsFormViewByVariantProduct()
    {
        $formView = $this->createMock(FormView::class);

        $product = $this->getProduct(1001);
        $variantProduct = $this->getProduct(1003);

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

        $formView1 = $this->provider->getVariantFieldsFormViewByVariantProduct($product, $variantProduct);
        $this->assertSame($formView, $formView1);
        // test memory cache
        $formView2 = $this->provider->getVariantFieldsFormViewByVariantProduct($product, $variantProduct);
        $this->assertSame($formView1, $formView2);
    }

    public function testGetVariantFieldsForm()
    {
        $product = $this->getProduct(1001);
        $productVariant = $this->getProduct(2002);

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

        $this->assertSame($form, $this->provider->getVariantFieldsForm($product));
        // test memory cache
        $this->assertSame($form, $this->provider->getVariantFieldsForm($product));
    }

    private function getProductVariantExpectedOptions(Product $product, int $expects = 1): array
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
