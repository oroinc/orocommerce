<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductUnitValueFormatterTest extends UnitValueFormatterTestCase
{
    const TRANSLATION_PREFIX = 'oro.product_unit';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new ProductUnitValueFormatter($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }
}
