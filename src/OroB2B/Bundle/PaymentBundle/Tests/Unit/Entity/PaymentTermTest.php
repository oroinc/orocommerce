<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['label', 'net 10']
        ];

        $this->assertPropertyAccessors(new PaymentTerm(), $properties);
    }
}
