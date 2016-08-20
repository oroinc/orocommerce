<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use Symfony\Component\Validator\Constraint;

class UniquePriceListTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTargets()
    {
        $constraint = new UniquePriceList();
        $this->assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
