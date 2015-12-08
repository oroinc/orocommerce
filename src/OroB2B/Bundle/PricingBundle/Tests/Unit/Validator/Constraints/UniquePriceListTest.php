<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use Symfony\Component\Validator\Constraint;

class UniquePriceListTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTargets()
    {
        $constraint = new UniquePriceList();
        $this->assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
