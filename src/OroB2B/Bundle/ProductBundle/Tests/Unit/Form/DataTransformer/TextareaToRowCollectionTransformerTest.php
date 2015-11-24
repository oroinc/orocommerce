<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\TextareaToRowCollectionTransformer;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;

class TextareaToRowCollectionTransformerTest extends \PHPUnit_Framework_TestCase
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
     * @param int $countAll
     * @param int $countComplete
     * @param array $elements
     */
    public function testTransformsСommaSeparatedLines($string, $countAll, $countComplete, $elements)
    {
        /** @var QuickAddRow[]|QuickAddRowCollection $collection */
        $collection = $this->transformer->reverseTransform($string);

        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection', $collection);
        $this->assertCount($countAll, $collection);
        $this->assertCount($countComplete, $collection->getCompleteRows());

        foreach ($collection as $i => $element) {
            $this->assertEquals($elements[$element->getSku()], $element->getQuantity());
        }
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
        $elements = [
            'HSSUC' => 1,
            'HSTUC' => 2.55,
            'HCCM' => 3,
            'SKU1' => 10.0112,
            'SKU2' => 0,
            'SKU3' => null
        ];

        return [
            'comma separated' => [$commaSeparated, 6, 4, $elements],
            'tabs separated' => [$tabsSeparated, 6, 4, $elements]
        ];
    }
}
