<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;

class UnitValueFormatterTest extends UnitValueFormatterTestCase
{
    const TRANSLATION_PREFIX = 'oro.product_unit';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new UnitValueFormatter($this->translator);
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
