<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Generator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MoneyOrderBundle\Method\Generator\MoneyOrderConfigIdentifierGenerator;

class MoneyOrderConfigIdentifierGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var MoneyOrderConfigIdentifierGenerator */
    private $generator;

    protected function setUp()
    {
        $this->generator = new MoneyOrderConfigIdentifierGenerator();
    }

    public function testGenerateIdentifierReturnsCorrectString()
    {
        $channelId = 7;

        $channel = $this->createMock(Channel::class);
        $channel->expects(static::once())
            ->method('getId')
            ->willReturn($channelId);

        static::assertSame(
            'money_order_' . $channelId,
            $this->generator->generateIdentifier($channel)
        );
    }
}
