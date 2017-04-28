<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntity;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;

class ApruveLineItemTest extends \PHPUnit_Framework_TestCase
{
    const DATA = [
        'merchantId' => 'sampleId',
    ];

    /**
     * @var AbstractApruveEntity
     */
    private $apruveLineItem;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->apruveLineItem = new ApruveLineItem(self::DATA);
    }

    public function testGetData()
    {
        static::assertSame(self::DATA, $this->apruveLineItem->getData());
    }
}
