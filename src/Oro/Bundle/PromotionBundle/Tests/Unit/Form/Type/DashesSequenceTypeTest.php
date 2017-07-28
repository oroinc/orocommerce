<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Form\Type\DashesSequenceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DashesSequenceTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DashesSequenceType
     */
    protected $dashesSequenceType;

    protected function setUp()
    {
        $this->dashesSequenceType = new DashesSequenceType();
    }

    public function testGetParent()
    {
        $this->assertEquals(IntegerType::class, $this->dashesSequenceType->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_dashes_sequence', $this->dashesSequenceType->getBlockPrefix());
    }
}
