<?php

namespace OroB2B\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\DataProvider\AbstractFormDataProvider;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\ProductLineItem;

class ProductFormDataProvider extends AbstractFormDataProvider
{
    const PRODUCT_QUICK_ADD_ROUTE_NAME              = 'orob2b_product_frontend_quick_add';
    const PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME   = 'orob2b_product_frontend_quick_add_copy_paste';
    const PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME       = 'orob2b_product_frontend_quick_add_import';

    /**
     * @return FormAccessor
     */
    public function getQuickAddForm()
    {
        return $this->getFormAccessor(QuickAddType::NAME, self::PRODUCT_QUICK_ADD_ROUTE_NAME);
    }

    /**
     * @param null $data
     * @param array $options
     *
     * @return FormAccessor
     */
    public function getBaseQuickAddForm($data = null, array $options = [])
    {
        return $this->getFormAccessor(QuickAddType::NAME, null, $data, [], $options);
    }

    /**
     * @return FormAccessor
     */
    public function getQuickAddCopyPasteForm()
    {
        return $this->getFormAccessor(QuickAddCopyPasteType::NAME, self::PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME);
    }

    /**
     * @return FormAccessor
     */
    public function getQuickAddImportForm()
    {
        return $this->getFormAccessor(QuickAddImportFromFileType::NAME, self::PRODUCT_QUICK_ADD_ROUTE_NAME);
    }

    /**
     * @param Product|null $product
     *
     * @return FormAccessor
     */
    public function getLineItemForm(Product $product = null)
    {
        $lineItem = new ProductLineItem(null);
        if ($product) {
            $lineItem->setProduct($product);
        }

        // in this context parameters used for generating local cache
        $parameters = $product ? ['id' => $product->getId()] : [];
        return $this->getFormAccessor(FrontendLineItemType::NAME, null, $lineItem, $parameters);
    }
}
