<?php

namespace Oro\Bundle\DPDBundle\Tests\Units\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodIdentifierGenerator;

class DPDMethodIdentifierGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateIdentifier()
    {
        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $generator = new DPDMethodIdentifierGenerator();

        $this->assertEquals('dpd_1', $generator->generateIdentifier($channel));
    }
}
