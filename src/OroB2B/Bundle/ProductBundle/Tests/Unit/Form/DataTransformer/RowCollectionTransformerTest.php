<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;

abstract class RowCollectionTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $expectedElements = [
        'HSSUC' => 1,
        'HSTUC' => 2.55,
        'HCCM' => 3,
        'SKU1' => 10.0112,
        'SKU2' => 0,
        'SKU3' => null
    ];

    /**
     * @var int
     */
    protected $expectedCountAll = 6;

    /**
     * @var int
     */
    protected $expectedCountValid = 4;

    protected function assertValidCollection(QuickAddRowCollection $collection)
    {
        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection', $collection);
        $this->assertCount($this->expectedCountAll, $collection);
        $this->assertCount($this->expectedCountValid, $collection->getCompleteRows());

        foreach ($collection as $i => $element) {
            $this->assertEquals($this->expectedElements[$element->getSku()], $element->getQuantity());
        }
    }
}
