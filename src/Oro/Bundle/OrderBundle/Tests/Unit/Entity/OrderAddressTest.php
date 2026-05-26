<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class OrderAddressTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['phone', '555-1234'],
            ['fromExternalSource', true],
            ['draftSessionUuid', '8f091a9a-c0d7-4560-975a-d3b0090bcfbd'],
        ];

        self::assertPropertyAccessors(new OrderAddress(), $properties);
    }
}
