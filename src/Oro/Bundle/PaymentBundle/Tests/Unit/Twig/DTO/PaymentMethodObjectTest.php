<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig\DTO;

use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentMethodObjectTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $label = 'some label';
        $options = ['some options'];
        $properties = [
            ['label', $label, false],
            ['options', $options, false],
        ];

        self::assertPropertyAccessors(new PaymentMethodObject($label, $options), $properties);
    }
}
