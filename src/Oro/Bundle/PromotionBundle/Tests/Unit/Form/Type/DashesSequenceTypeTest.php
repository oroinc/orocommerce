<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Form\Type\DashesSequenceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DashesSequenceTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DashesSequenceType
     */
    protected $dashesSequenceType;

    protected function setUp(): void
    {
        $this->dashesSequenceType = new DashesSequenceType();
    }

    public function testGetParent()
    {
        $this->assertEquals(IntegerType::class, $this->dashesSequenceType->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_promotion_coupon_dashes_sequence', $this->dashesSequenceType->getBlockPrefix());
    }
}
