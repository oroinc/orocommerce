<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class PriceListSelectTypeStub extends StubEntityType
{
    use EntityTrait;

    const PRICE_LIST_1 = 1;
    const PRICE_LIST_2 = 2;
    const PRICE_LIST_3 = 3;

    /**
     * PriceListSelectTypeStub constructor.
     */
    public function __construct()
    {
        parent::__construct([
            self::PRICE_LIST_1 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', [
                'id' => self::PRICE_LIST_1
            ]),
            self::PRICE_LIST_2 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', [
                'id' => self::PRICE_LIST_2
            ]),
            self::PRICE_LIST_3 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', [
                'id' => self::PRICE_LIST_3
            ])
        ], PriceListSelectType::NAME);
    }
}
