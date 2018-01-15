<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ShippingServiceRuleTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new ShippingServiceRule(), [
            ['id', 12],
            ['limitationExpressionLbs', 'limitationExpressionLbs'],
            ['limitationExpressionKg', 'limitationExpressionKg'],
            ['serviceType', 'serviceType'],
            ['residentialAddress', false],
        ]);
    }
}
