<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\TextareaToRowCollectionTransformer;

class TextareaToRowCollectionTransformerTest extends RowCollectionTransformerTest
{
    /**
     * @var TextareaToRowCollectionTransformer
     */
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = new TextareaToRowCollectionTransformer();
        $this->transformer->transform('');
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param string $string
     */
    public function testReverseTransform($string)
    {
        $this->assertValidCollection($this->transformer->reverseTransform($string));
    }

    public function reverseTransformDataProvider()
    {
        $commaSeparated = <<<TEXT
HSSUC, 1
HSTUC, 2.55
HCCM, 3,
SKU1,10.0112
SKU2,asd
SKU3,
TEXT;
        $tabsSeparated = <<<TEXT
HSSUC\t1
HSTUC\t2.55
HCCM\t3\t
SKU1\t10.0112
SKU2\tasd
SKU3\t
TEXT;

        return [
            'comma separated' => [$commaSeparated],
            'tabs separated' => [$tabsSeparated]
        ];
    }
}
