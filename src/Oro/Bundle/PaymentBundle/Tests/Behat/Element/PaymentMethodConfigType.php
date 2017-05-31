<?php

namespace Oro\Bundle\PaymentBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class PaymentMethodConfigType extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $item) {
            $parentField = $this->getParent()->getParent()->getParent()->getParent();
            $field = $parentField->find('css', 'select');
            self::assertNotNull($field, 'Select payment method field not found');
            $field->setValue($item);
            $parentField->clickLink('Add');
            $this->getDriver()->waitForAjax();
        }
    }
}
