<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Formatter;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter\LabelFormatterTestCase;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Formatter\FreightClassLabelFormatter;

class FreightClassLabelFormatterTest extends LabelFormatterTestCase
{
    const TRANSLATION_PREFIX = 'orob2b.freight_class';

    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new FreightClassLabelFormatter($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new FreightClass();
        $unit->setCode($code);

        return $unit;
    }
}
