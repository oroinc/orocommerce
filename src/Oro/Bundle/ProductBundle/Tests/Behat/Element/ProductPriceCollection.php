<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
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

            $this->changeRowValue($row, $value);
        }
    }

    public function assertRows(array $values)
    {
        $rows = $this->findAll('css', '.product-price-collection .oro-multiselect-holder');

        $collectionValues = [];
        foreach ($rows as $row) {
            $collectionValues[] = $this->getRowValues($row);
        }

        static::assertEquals($values, $collectionValues, 'Product Price Collection contains wrong data');
    }

    /**
     * @param int $number
     * @param array $values
     */
    public function changeRow($number, array $values)
    {
        $row = $this->getRowByNumber($number);

        $this->changeRowValue($row, $values);
    }

    /**
     * @param int $number
     * @return NodeElement|mixed|null
     */
    private function getRowByNumber($number)
    {
        $row = $this->find('xpath', sprintf('(//*[contains(@class, "oro-multiselect-holder")])[%s]', $number));

        if (!$row) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find Product Price collection element with %s number', $number)
            );
        }

        return $row;
    }

    /**
     * @param NodeElement $row
     * @return array
     */
    private function getRowValues(NodeElement $row)
    {
        /** @var Select2Entity $element */
        $element = $this->elementFactory->wrapElement(
            'Select2Entity',
            $row->find('xpath', '//input[contains(@id,"priceList")]')
        );

        return [
            'Price List' => $element->getValue(),
            'Quantity value' => $row->find('xpath', '//input[contains(@id,"quantity")]')->getValue(),
            'Quantity Unit' => $row->find('xpath', '//select[contains(@id,"unit")]')->getValue(),
            'Value' => $row->find('xpath', '//input[contains(@id,"price_value")]')->getValue()
        ];
    }

    private function changeRowValue(NodeElement $row, array $value)
    {
        $element = $this->elementFactory->wrapElement(
            'Select2Entity',
            $row->find('xpath', '//input[contains(@id,"priceList")]')
        );

        $element->setValue($value['Price List']);

        $row->find('xpath', '//input[contains(@id,"quantity")]')->setValue($value['Quantity value']);
        $row->find('xpath', '//select[contains(@id,"unit")]')->setValue($value['Quantity Unit']);
        $row->find('xpath', '//input[contains(@id,"price_value")]')->setValue($value['Value']);

        if (isset($value['Currency'])) {
            $row->find('xpath', '//select[contains(@id,"price_currency")]')->setValue($value['Currency']);
        }
    }
}
