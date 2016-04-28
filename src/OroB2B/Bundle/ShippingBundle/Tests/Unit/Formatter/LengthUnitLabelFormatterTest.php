<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Formatter;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter\LabelFormatterTestCase;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Formatter\LengthUnitLabelFormatter;

class ProductUnitLabelFormatterTest extends LabelFormatterTestCase
{
    const TRANSLATION_PREFIX = 'orob2b.length_unit';

    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new LengthUnitLabelFormatter($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new LengthUnit();
        $unit->setCode($code);

        return $unit;
    }
}
