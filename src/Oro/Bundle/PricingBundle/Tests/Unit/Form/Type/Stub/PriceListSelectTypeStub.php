<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;

class PriceListSelectTypeStub extends EntityTypeStub
{
    public const PRICE_LIST_1 = 1;
    public const PRICE_LIST_2 = 2;
    public const PRICE_LIST_3 = 3;

    public function __construct()
    {
        parent::__construct([
            self::PRICE_LIST_1 => $this->getPriceList(self::PRICE_LIST_1),
            self::PRICE_LIST_2 => $this->getPriceList(self::PRICE_LIST_2),
            self::PRICE_LIST_3 => $this->getPriceList(self::PRICE_LIST_3)
        ]);
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }
}
