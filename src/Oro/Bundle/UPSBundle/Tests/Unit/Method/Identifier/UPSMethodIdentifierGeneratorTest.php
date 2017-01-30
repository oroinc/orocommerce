<?php

namespace Oro\Bundle\UPSBundle\Tests\Units\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodIdentifierGenerator;

class UPSMethodIdentifierGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateIdentifier()
    {
        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $generator = new UPSMethodIdentifierGenerator();

        $this->assertEquals('ups_1', $generator->generateIdentifier($channel));
    }
}
