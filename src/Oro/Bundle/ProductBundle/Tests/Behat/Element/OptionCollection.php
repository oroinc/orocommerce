<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;

class OptionCollection extends CollectionField
{
    /**
     * @param TableNode $table
     */
    public function setValue($table)
    {
        $this->removeAllRows();
        $this->addNewRows($table);
        $rows = $this->findAll('css', '.oro-multiselect-holder');
        foreach ($table as $values) {
            /** @var NodeElement $row */
            $row       = array_shift($rows);
            $rowNumber = $row->getParent()->getAttribute('data-content');
            $label     = sprintf('//input[contains(@id,"options_%s_label")]', $rowNumber);

            $row->find('xpath', $label)->setValue($values['Label']);
        }
    }
}
