<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\NotificationMessage;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class NotificationMessageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new NotificationMessage(), [
            ['id', 42],
            ['message', 'some string'],
            ['messageStatus', 'some_string'],
            ['channel', 'some_string'],
            ['topic', 'some_string'],
            ['receiverEntityFQCN', '\SomeClass'],
            ['receiverEntityId', 5],
            ['resolved', true],
            ['resolvedAt', new \DateTime()],
            ['createdAt', new \DateTime()]
        ]);
    }
}
