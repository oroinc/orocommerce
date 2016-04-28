<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Formatter;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter\LabelFormatterTestCase;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Formatter\ WeightUnitLabelFormatter;

class WeightUnitLabelFormatterTest extends LabelFormatterTestCase
{
    const TRANSLATION_PREFIX = 'orob2b.weight_unit';

    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new WeightUnitLabelFormatter($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new WeightUnit();
        $unit->setCode($code);

        return $unit;
    }
}
