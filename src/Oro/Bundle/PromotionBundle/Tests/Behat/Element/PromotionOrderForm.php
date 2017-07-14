<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;

class PromotionOrderForm extends OroForm
{
    public function saveWithoutDiscountsRecalculation()
    {
        $this->pressActionButton('Save Without Discounts Recalculation');
    }
}
