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

        $variants = $this->find(
            'css',
            'div[data-name="field__variant-fields"]'
        );
        self::assertNotNull($variants, "Variants block not found");

        foreach ($value as $item) {
            // Click on label because stylish checkbox hover the real one
            $checkBox = $variants->find('xpath', '//label[text()="'. $item .'"]');
            self::assertNotNull($checkBox, "Label for checkbox '$item' not found");
            $checkBox->click();
        }
    }
}
