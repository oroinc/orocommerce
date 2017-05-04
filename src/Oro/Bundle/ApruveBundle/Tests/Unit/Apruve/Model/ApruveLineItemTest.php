<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;

class ApruveLineItemTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'sampleId';
    const DATA = [
        'id' => self::ID,
        'merchantId' => 'sampleId',
    ];

    /**
     * @var ApruveLineItem
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

    public function testGetId()
    {
        static::assertSame(self::ID, $this->apruveLineItem->getId());
    }
}
