<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Request;

use Oro\Bundle\ApruveBundle\Apruve\Request\ApruveRequestData;

class ApruveRequestDataTest extends \PHPUnit_Framework_TestCase
{
    const DATA = [
        'merchantId' => 'sampleId',
    ];

    /**
     * @var ApruveRequestData
     */
    private $requestData;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->requestData = new ApruveRequestData(self::DATA);
    }

    public function testGetData()
    {
        static::assertSame(self::DATA, $this->requestData->getData());
    }
}
