<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

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
        if (!$this->data || !isset($this->data['withOffers'])) {
            return;
        }

        foreach ($view->offsetGet('lineItems')->children as $rowView) {
            foreach ($this->data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataRow) {
                if ($dataRow[ProductDataStorage::PRODUCT_SKU_KEY] == $rowView->vars['value']->getProductSku()) {
                    $rowView->vars['offers'] = $dataRow['offers'];
                }
            }
            $rowView->vars['sections']->set('offers', ['data' => [], 'order' => 5]);
            $rowView->vars['sections'] = $this->sortSections($rowView->vars['sections']);
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
        $iterator = $sections->getIterator();
        $iterator->uasort(
            function ($a, $b) {
                return ($a['order'] < $b['order']) ? -1 : 1;
            }
        );

        return new ArrayCollection(iterator_to_array($iterator));
    }
}
