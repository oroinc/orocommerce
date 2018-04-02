<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;

class AdditionalUnitCollection extends CollectionField
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $existingRows = $this->findAll('xpath', '//tr[contains(@data-content,"additionalUnitPrecisions")]');
        $existingRowsCount = count($existingRows);

        $this->addNewRows($values);
        $rows = $this->findAll('xpath', '//tr[contains(@data-content,"additionalUnitPrecisions")]');

        foreach ($values as $key => $value) {
            /** @var NodeElement $row */
            $row = $rows[$existingRowsCount + $key];

            $row->find('xpath', '//input[contains(@id,"precision")]')->setValue((int)$value['Precision']);
            $row->find('xpath', '//input[contains(@id,"conversionRate")]')->setValue((int)$value['Rate']);
        }
    }
}
