<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

class DiscountInformationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $amount = 4.2;
        $info = new DiscountInformation($discount, $amount);
        $this->assertSame($discount, $info->getDiscount());
        $this->assertSame($amount, $info->getDiscountAmount());
    }
}
