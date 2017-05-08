<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ProductCustomVariantFieldsCollection extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $value = is_array($value) ? $value : [$value];

        foreach ($value as $item) {
            // Click on label because stylish checkbox hover the real one
            $checkBox = $this->find('css', "input[type='checkbox'][id^=oro_product_variantFields_$item]");
            self::assertNotNull($checkBox, "Checkbox '$item' not found");
            $checkBoxLabel = $checkBox
                ->getParent()
                ->getParent()
                ->find('css', 'label');
            self::assertNotNull($checkBoxLabel, "Label for checkbox '$item' not found");
            $checkBoxLabel->click();
        }
    }
}
