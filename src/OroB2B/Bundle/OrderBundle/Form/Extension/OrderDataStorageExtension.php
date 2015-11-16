<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;

class OrderDataStorageExtension extends FrontendOrderDataStorageExtension
{
    /**
     * {@inheritdoc}
     */
    protected function fillItemsData($entity, array $itemsData = [])
    {
        $repository = $this->getProductRepository();
        foreach ($itemsData as $dataRow) {
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
                continue;
            }

            $product = $repository->findOneBySku($dataRow[ProductDataStorage::PRODUCT_SKU_KEY]);
            if (!$product) {
                continue;
            }

            $this->addItem($product, $entity, $dataRow);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $this->data;
        if (isset($options['storage_data'])) {
            $data = array_replace_recursive($data, $options['data']);
        }
        if (!$data || !isset($data['withOffers'])) {
            return;
        }

        $offers = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataRow) {
            $offers[$dataRow[ProductDataStorage::PRODUCT_SKU_KEY]] = $dataRow['offers'];
        }

        foreach ($view->offsetGet('lineItems')->children as $rowView) {

            /**
             * @var OrderLineItem $lineItem
             */
            $lineItem = $rowView->vars['value'];
            $sku = $lineItem->getProductSku();
            if (isset($offers[$sku])) {
                $rowView->vars['offers'] = $offers[$sku];
            }

            /**
             * @var ArrayCollection $sections
             */
            $sections = $rowView->vars['sections'];
            $sections->set('offers', ['data' => [], 'order' => 5]);
            $rowView->vars['sections'] = $this->sortSections($sections);
        }

        $sections = $view->offsetGet('lineItems')->vars['prototype']->vars['sections'];
        $sections->set('offers', ['data' => [], 'order' => 5]);
        $view->offsetGet('lineItems')->vars['prototype']->vars['sections'] = $this->sortSections($sections);
    }

    /**
     * @param ArrayCollection $sections
     * @return ArrayCollection
     */
    protected function sortSections(ArrayCollection $sections)
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => Criteria::ASC]);

        return $sections->matching($criteria);
    }
}
