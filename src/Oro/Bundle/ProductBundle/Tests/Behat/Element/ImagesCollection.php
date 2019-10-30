<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ImagesCollection extends CollectionField
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $existingRows = $this->findAll('xpath', '//tr[contains(@data-content,"images")]');
        $existingRowsCount = count($existingRows);

        $this->addNewRows($values);
        $rows = $this->getElements('ImageCollectionRow');

        foreach ($values as $key => $value) {
            /** @var Element $row */
            $row = $rows[$existingRowsCount + $key];

            $fileField = $row->getElement('ImageCollectionFileField');
            if ($fileField->isValid()) {
                $fileField->setValue($value['File']);
            }

            if ($value['Main']) {
                $row->find('xpath', '//input[contains(@id,"main")]')->setValue($value['Main']);
            }
            if ($value['Listing']) {
                $row->find('xpath', '//input[contains(@id,"listing")]')->setValue($value['Listing']);
            }
            $row->find('xpath', '//input[contains(@id,"additional")]')->setValue($value['Additional']);
        }
    }
}
