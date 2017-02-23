<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Method\Identifier;

use Oro\Bundle\FlatRateShippingBundle\Method\Identifier\FlatRateMethodIdentifierGenerator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class FlatRateMethodIdentifierGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateIdentifier()
    {
        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $generator = new FlatRateMethodIdentifierGenerator();

        $this->assertEquals('flat_rate_1', $generator->generateIdentifier($channel));
    }
}
