<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;

class ProductPriceCollection extends CollectionField
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $this->removeAllRows();
        $this->addNewRows($values);

        $rows = $this->findAll('css', '.product-price-collection .oro-multiselect-holder');

        foreach ($values as $value) {
            /** @var NodeElement $row */
            $row = array_shift($rows);

            $element = $this->elementFactory->wrapElement(
                'Select2Entity',
                $row->find('xpath', '//input[contains(@id,"priceList")]')
            );
            
            $element->setValue($value['Price List']);

            $row->find('xpath', '//input[contains(@id,"quantity")]')->setValue($value['Quantity value']);
            $row->find('xpath', '//select[contains(@id,"unit")]')->setValue($value['Quantity Unit']);
            $row->find('xpath', '//input[contains(@id,"price_value")]')->setValue($value['Value']);
        }
    }
}
