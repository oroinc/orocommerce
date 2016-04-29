<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

class UnitLabelFormatterTest extends UnitLabelFormatterTestCase
{
    const TRANSLATION_PREFIX = 'orob2b.product_unit';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new UnitLabelFormatter($this->translator);
        $this->formatter->setTranslationPrefix(static::TRANSLATION_PREFIX);
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
