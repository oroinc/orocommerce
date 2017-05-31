<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShippingMethodConfigType extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $parentField = $this->getParent()->getParent()->getParent()->getParent();
        parent::setValue($value);
        $parentField->clickLink('Add');
        $this->getDriver()->waitForAjax();
    }
}
