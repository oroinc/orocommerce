<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
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
    private FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $formFactory;

    private UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $router;

    private ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject
        $productVariantAvailabilityProvider;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private ProductFormProvider $provider;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductFormProvider(
            $this->formFactory,
            $this->router,
            $this->productVariantAvailabilityProvider,
            $this->doctrine
        );

        $this->provider->setConfigManager($this->configManager);
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    public function testGetQuickAddFormView(): void
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddType::class)
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormView::class, $this->provider->getQuickAddFormView());
        // test memory cache
        self::assertInstanceOf(FormView::class, $this->provider->getQuickAddFormView());
    }

    public function testGetQuickAddForm(): void
    {
        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddType::class)
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormInterface::class, $this->provider->getQuickAddForm());
        // test memory cache
        self::assertInstanceOf(FormInterface::class, $this->provider->getQuickAddForm());
    }

    public function testGetQuickAddCopyPasteFormView(): void
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddCopyPasteType::class)
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormView::class, $this->provider->getQuickAddCopyPasteFormView());
        // test memory cache
        self::assertInstanceOf(FormView::class, $this->provider->getQuickAddCopyPasteFormView());
    }

    public function testGetQuickAddCopyPasteForm(): void
    {
        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddCopyPasteType::class)
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormInterface::class, $this->provider->getQuickAddCopyPasteForm());
        // test memory cache
        self::assertInstanceOf(FormInterface::class, $this->provider->getQuickAddCopyPasteForm());
    }

    public function testGetQuickAddCopyPasteFormViewWhenIsOptimized()
    {
        $this->configManager
            ->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED))
            ->willReturn(true);
        $action = '/import/copy-paste';
        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->with('oro_product_frontend_quick_add_copy_paste')
            ->willReturn($action);

        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddCopyPasteType::class, null, ['action' => $action, 'is_optimized' => true])
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteFormView();
        self::assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteFormView();
        self::assertInstanceOf(FormView::class, $data);
    }

    public function testGetQuickAddCopyPasteFormWhenIsOptimized(): void
    {
        $this->configManager
            ->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED))
            ->willReturn(true);
        $action = '/import/copy-paste';
        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->with('oro_product_frontend_quick_add_copy_paste')
            ->willReturn($action);

        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddCopyPasteType::class, null, ['action' => $action, 'is_optimized' => true])
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteForm();
        self::assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddCopyPasteForm();
        self::assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetQuickAddImportFormView(): void
    {
        $this->configManager
            ->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED))
            ->willReturn(false);
        $action = '/import';
        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->with('oro_product_frontend_quick_add_import')
            ->willReturn($action);

        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddImportFromFileType::class, null, ['action' => $action, 'is_optimized' => false])
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormView::class, $this->provider->getQuickAddImportFormView());
        // test memory cache
        self::assertInstanceOf(FormView::class, $this->provider->getQuickAddImportFormView());
    }

    public function testGetQuickAddImportFormViewWhenIsOptimized(): void
    {
        $this->configManager
            ->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED))
            ->willReturn(true);
        $action = '/import';
        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->with('oro_product_frontend_quick_add_import')
            ->willReturn($action);

        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddImportFromFileType::class, null, ['action' => $action, 'is_optimized' => true])
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddImportFormView();
        self::assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddImportFormView();
        self::assertInstanceOf(FormView::class, $data);
    }

    public function testGetQuickAddImportForm(): void
    {
        $this->configManager
            ->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED))
            ->willReturn(false);
        $action = '/import';
        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->with('oro_product_frontend_quick_add_import')
            ->willReturn($action);

        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddImportFromFileType::class, null, ['action' => $action, 'is_optimized' => false])
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormInterface::class, $this->provider->getQuickAddImportForm());
        // test memory cache
        self::assertInstanceOf(FormInterface::class, $this->provider->getQuickAddImportForm());
    }

    public function testGetQuickAddImportFormWhenIsOptimized(): void
    {
        $this->configManager
            ->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED))
            ->willReturn(true);
        $action = '/import';
        $this->router
            ->expects(self::exactly(2))
            ->method('generate')
            ->with('oro_product_frontend_quick_add_import')
            ->willReturn($action);

        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(QuickAddImportFromFileType::class, null, ['action' => $action, 'is_optimized' => true])
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getQuickAddImportForm();
        self::assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getQuickAddImportForm();
        self::assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetLineItemFormView(): void
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FrontendLineItemType::class)
            ->willReturn($expectedForm);

        self::assertInstanceOf(FormView::class, $this->provider->getLineItemFormView(null));
        // test memory cache
        self::assertInstanceOf(FormView::class, $this->provider->getLineItemFormView(null));
    }

    public function testGetLineItemFormViewWithInstanceName(): void
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::exactly(2))
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::exactly(2))
            ->method('create')
            ->with(FrontendLineItemType::class)
            ->willReturn($expectedForm);

        $data1 = $this->provider->getLineItemFormView(null, 'form1');

        // test memory cache
        self::assertSame($data1, $this->provider->getLineItemFormView(null, 'form1'));

        // get new form instance
        $data2 = $this->provider->getLineItemFormView(null, 'form2');
        self::assertSame($data1, $data2);
        self::assertEquals($data1, $data2);
    }

    public function testGetLineItemFormViewWithProduct(): void
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FrontendLineItemType::class)
            ->willReturn($expectedForm);

        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        self::assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
        // test memory cache
        self::assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
    }

    public function testGetLineItemFormViewWithProductView(): void
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock(FormInterface::class);
        $expectedForm->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FrontendLineItemType::class)
            ->willReturn($expectedForm);

        $product = new ProductView();
        $product->set('id', 1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('getReference')
            ->with(Product::class, 1)
            ->willReturn($this->createMock(Product::class));

        self::assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
        // test memory cache
        self::assertInstanceOf(FormView::class, $this->provider->getLineItemFormView($product));
    }

    public function testGetVariantFieldsFormView(): void
    {
        $formView = $this->createMock(FormView::class);

        $product = $this->getProduct(1001);
        $productVariant = $this->getProduct(2002);

        $this->productVariantAvailabilityProvider->expects(self::atLeastOnce())
            ->method('getSimpleProductByVariantFields')
            ->with($product, [], false)
            ->willReturn($productVariant);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                FrontendVariantFiledType::class,
                $productVariant,
                $this->getProductVariantExpectedOptions($product, 2)
            )
            ->willReturn($form);

        self::assertSame($formView, $this->provider->getVariantFieldsFormView($product));
        // test memory cache
        self::assertSame($formView, $this->provider->getVariantFieldsFormView($product));
    }

    public function testGetVariantFieldsFormViewByVariantProduct(): void
    {
        $formView = $this->createMock(FormView::class);

        $product = $this->getProduct(1001);
        $variantProduct = $this->getProduct(1003);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                FrontendVariantFiledType::class,
                $variantProduct,
                $this->getProductVariantExpectedOptions($product, 2)
            )
            ->willReturn($form);

        $formView1 = $this->provider->getVariantFieldsFormViewByVariantProduct($product, $variantProduct);
        self::assertSame($formView, $formView1);
        // test memory cache
        $formView2 = $this->provider->getVariantFieldsFormViewByVariantProduct($product, $variantProduct);
        self::assertSame($formView1, $formView2);
    }

    public function testGetVariantFieldsForm(): void
    {
        $product = $this->getProduct(1001);
        $productVariant = $this->getProduct(2002);

        $this->productVariantAvailabilityProvider->expects(self::atLeastOnce())
            ->method('getSimpleProductByVariantFields')
            ->with($product, [], false)
            ->willReturn($productVariant);

        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                FrontendVariantFiledType::class,
                $productVariant,
                $this->getProductVariantExpectedOptions($product, 2)
            )
            ->willReturn($form);

        self::assertSame($form, $this->provider->getVariantFieldsForm($product));
        // test memory cache
        self::assertSame($form, $this->provider->getVariantFieldsForm($product));
    }

    private function getProductVariantExpectedOptions(Product $product, int $expects = 1): array
    {
        $this->router->expects(self::exactly($expects))
            ->method('generate')
            ->with('oro_product_frontend_ajax_product_variant_get_available', ['id' => $product->getId()])
            ->willReturn('product_variants_get_available_variants_url');

        return [
            'action' => 'product_variants_get_available_variants_url',
            'parentProduct' => $product,
            'dynamic_fields_disabled' => true,
        ];
    }
}
