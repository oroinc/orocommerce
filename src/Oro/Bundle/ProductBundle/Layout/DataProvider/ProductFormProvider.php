<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\ProductBundle\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;

class ProductFormProvider extends AbstractFormProvider
{
    const PRODUCT_QUICK_ADD_ROUTE_NAME              = 'oro_product_frontend_quick_add';
    const PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME   = 'oro_product_frontend_quick_add_copy_paste';
    const PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME       = 'oro_product_frontend_quick_add_import';

    /**
     * @param null  $data
     * @param array $options
     *
     * @return FormView
     */
    public function getQuickAddFormView($data = null, array $options = [])
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_ROUTE_NAME);
        $cacheKeyOptions = $this->getQuickAddFormCacheKeyOptions();

        return $this->getFormView(QuickAddType::NAME, $data, $options, $cacheKeyOptions);
    }

    /**
     * @param null  $data
     * @param array $options
     *
     * @return FormInterface
     */
    public function getQuickAddForm($data = null, array $options = [])
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_ROUTE_NAME);
        $cacheKeyOptions = $this->getQuickAddFormCacheKeyOptions();

        return $this->getForm(QuickAddType::NAME, $data, $options, $cacheKeyOptions);
    }

    /**
     * @return FormView
     */
    public function getQuickAddCopyPasteFormView()
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME);

        return $this->getFormView(QuickAddCopyPasteType::NAME, null, $options);
    }

    /**
     * @return FormInterface
     */
    public function getQuickAddCopyPasteForm()
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME);

        return $this->getForm(QuickAddCopyPasteType::NAME, null, $options);
    }

    /**
     * @return FormView
     */
    public function getQuickAddImportFormView()
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME);

        return $this->getFormView(QuickAddImportFromFileType::NAME, null, $options);
    }

    /**
     * @return FormInterface
     */
    public function getQuickAddImportForm()
    {
        $options['action'] = $this->generateUrl(self::PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME);

        return $this->getForm(QuickAddImportFromFileType::NAME, null, $options);
    }

    /**
     * @param Product|null $product
     * @param string       $instanceName
     *
     * @return FormView
     */
    public function getLineItemFormView(Product $product = null, $instanceName = '')
    {
        $cacheKeyOptions = ['instanceName' => $instanceName];

        $lineItem = new ProductLineItem(null);

        if ($product) {
            $lineItem->setProduct($product);
            $cacheKeyOptions['id'] = $product->getId();
        }

        return $this->getFormView(FrontendLineItemType::NAME, $lineItem, [], $cacheKeyOptions);
    }

    /**
     * @param Product $product
     * @return FormView
     */
    public function getVariantFieldsView(Product $product)
    {
        return $this->getFormView(FrontendVariantFiledType::NAME, null, ['product' => $product]);
    }

    /**
     * @return array
     */
    private function getQuickAddFormCacheKeyOptions()
    {
        return [
            'products' => null,
            'validation_required' => null,
            'validation_groups' => null
        ];
    }
}
