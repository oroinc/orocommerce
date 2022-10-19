<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides form and form view for product view pages and quick order form.
 */
class ProductFormProvider extends AbstractFormProvider
{
    private const PRODUCT_QUICK_ADD_ROUTE_NAME = 'oro_product_frontend_quick_add';
    private const PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME = 'oro_product_frontend_quick_add_copy_paste';
    private const PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME = 'oro_product_frontend_quick_add_import';
    private const PRODUCT_VARIANTS_GET_AVAILABLE_VARIANTS = 'oro_product_frontend_ajax_product_variant_get_available';

    private ProductVariantAvailabilityProvider $productVariantAvailabilityProvider;

    private ManagerRegistry $doctrine;

    public function __construct(
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $router,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($formFactory, $router);
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->doctrine = $doctrine;
    }

    public function getQuickAddFormView(): FormView
    {
        $options = ['action' => $this->generateUrl(self::PRODUCT_QUICK_ADD_ROUTE_NAME)];
        $cacheKeyOptions = $this->getQuickAddFormCacheKeyOptions();

        return $this->getFormView(QuickAddType::class, null, $options, $cacheKeyOptions);
    }

    public function getQuickAddForm(array $data = null, array $options = []): FormInterface
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_ROUTE_NAME);
        $cacheKeyOptions = $this->getQuickAddFormCacheKeyOptions();

        return $this->getForm(QuickAddType::class, $data, $options, $cacheKeyOptions);
    }

    public function getQuickAddCopyPasteFormView(): FormView
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME);

        return $this->getFormView(QuickAddCopyPasteType::class, null, $options);
    }

    public function getQuickAddCopyPasteForm(): FormInterface
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME);

        return $this->getForm(QuickAddCopyPasteType::class, null, $options);
    }

    public function getQuickAddImportFormView(): FormView
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME);

        return $this->getFormView(QuickAddImportFromFileType::class, null, $options);
    }

    public function getQuickAddImportForm(): FormInterface
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME);

        return $this->getForm(QuickAddImportFromFileType::class, null, $options);
    }

    public function getLineItemFormView(Product|ProductView|null $product, string $instanceName = ''): FormView
    {
        $cacheKeyOptions = ['instanceName' => $instanceName];
        $lineItem = new ProductLineItem(null);
        if (null !== $product) {
            $cacheKeyOptions['id'] = $product->getId();
            $lineItem->setProduct(
                $product instanceof ProductView
                    ? $this->getProductReference($product->getId())
                    : $product
            );
        }

        return $this->getFormView(FrontendLineItemType::class, $lineItem, [], $cacheKeyOptions);
    }

    public function getVariantFieldsForm(Product $product): FormInterface
    {
        $data = $this->getVariantFieldsFormData($product);
        $options = $this->getVariantFieldsFormOptions($product);

        return $this->getForm(FrontendVariantFiledType::class, $data, $options, ['parentProduct' => $product->getId()]);
    }

    public function getVariantFieldsFormViewByVariantProduct(Product $product, Product $variantProduct): FormView
    {
        $options = $this->getVariantFieldsFormOptions($product);

        return $this->getFormView(
            FrontendVariantFiledType::class,
            $variantProduct,
            $options,
            ['parentProduct' => $product->getId()]
        );
    }

    public function getVariantFieldsFormView(Product $product): FormView
    {
        $data = $this->getVariantFieldsFormData($product);
        $options = $this->getVariantFieldsFormOptions($product);

        return $this->getFormView(
            FrontendVariantFiledType::class,
            $data,
            $options,
            ['parentProduct' => $product->getId()]
        );
    }

    private function getQuickAddFormCacheKeyOptions(): array
    {
        return [
            'products' => null,
            'validation_required' => null,
            'validation_groups' => null
        ];
    }

    private function getVariantFieldsFormData(Product $product): ?Product
    {
        return $this->productVariantAvailabilityProvider->getSimpleProductByVariantFields($product, [], false);
    }

    private function getVariantFieldsFormOptions(Product $product): array
    {
        return [
            'action' => $this->generateUrl(
                self::PRODUCT_VARIANTS_GET_AVAILABLE_VARIANTS,
                ['id' => $product->getId()]
            ),
            'parentProduct' => $product,
            'dynamic_fields_disabled' => true
        ];
    }

    private function getProductReference(int $productId): Product
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);

        return $em->getReference(Product::class, $productId);
    }
}
