<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeOutputProductPriceId;

class NormalizeOutputProductPriceIdTest extends GetListProcessorTestCase
{
    /** @var NormalizeOutputProductPriceId */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new NormalizeOutputProductPriceId();
    }

    public function testProcessNoResult()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessResultNotArray()
    {
        $result = 1;

        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertSame($result, $this->context->getResult());
    }

    public function testProcessOneEntity()
    {
        $priceListId = 12;
        $oldId = 'id';
        $newId = 'id-12';

        $this->context->set('price_list_id', $priceListId);
        $this->context->setResult(['id' => $oldId]);
        $this->processor->process($this->context);
        self::assertSame(['id' => $newId], $this->context->getResult());
    }

    public function testProcessMultipleEntities()
    {
        $priceListId = 12;
        $oldIds = [
            ['id' => 'id1'],
            ['id' => 'id2'],
            ['other' => 'id3']
        ];
        $newIds = [
            ['id' => 'id1-12'],
            ['id' => 'id2-12'],
            ['other' => 'id3']
        ];

        $this->context->set('price_list_id', $priceListId);
        $this->context->setResult($oldIds);
        $this->processor->process($this->context);
        self::assertSame($newIds, $this->context->getResult());
    }
}
