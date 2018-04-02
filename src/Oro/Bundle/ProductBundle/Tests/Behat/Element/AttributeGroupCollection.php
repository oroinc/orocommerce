<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class AttributeGroupCollection extends CollectionField
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $existingRows = $this->findAll('xpath', '//div[contains(@data-content,"attribute_family")]');
        $existingRowsCount = count($existingRows);

        $emptyRow = $this->findAll('xpath', '//div[contains(@data-content,"0")]');
        if ($emptyRow) {
            $this->addNewRows($values, -1);
        } else {
            $this->addNewRows($values);
        }

        $rows = $this->findAll('css', '[data-content]');
        foreach ($values as $key => $value) {
            /** @var NodeElement $row */
            $row = $rows[$existingRowsCount + $key];
            $rowNumber = $row->getAttribute('data-content');

            $label = sprintf('//input[contains(@id,"attributeGroups_%s_labels_values_default")]', $rowNumber);
            $visible = sprintf('//input[contains(@id,"attributeGroups_%s_isVisible")]', $rowNumber);
            $attributes = sprintf('//select[contains(@id,"attributeGroups_%s_attributeRelations")]', $rowNumber);

            $row->find('xpath', $label)->setValue($value['Label']);
            $row->find('xpath', $visible)->setValue($value['Visible']);
            $row->find('xpath', $attributes)->setValue(Form::normalizeValue($value['Attributes']));
        }
    }
}
