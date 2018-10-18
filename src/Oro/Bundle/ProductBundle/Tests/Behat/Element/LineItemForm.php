<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\EntityPage;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class LineItemForm extends EntityPage
{
    const SELECTOR_ITEM_LABEL = '.form-row label';

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsValue($label, $value)
    {
        $rowLabel = $this->find(
            'css',
            $this->selectorManipulator->addContainsSuffix(self::SELECTOR_ITEM_LABEL, $label)
        );

        if ($rowLabel === null) {
            self::fail(sprintf('Can\'t find "%s" label', $label));
        }

        $rowValue = $rowLabel->getParent()->find('css', 'select');
        $rowValueOption = $rowValue->find('named', ['option', Form::normalizeValue($value)]);

        if ($rowValueOption === null) {
            self::fail(sprintf('Found "%s" label, but it doesn\'t have "%s" value', $label, $value));
        }

        if (!$rowValueOption->isSelected()) {
            self::fail(sprintf('Value "%s" is not selected', $label));
        }
    }
}
