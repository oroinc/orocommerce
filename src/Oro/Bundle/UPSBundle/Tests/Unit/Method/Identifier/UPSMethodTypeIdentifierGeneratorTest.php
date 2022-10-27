<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodTypeIdentifierGenerator;

class UPSMethodTypeIdentifierGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateIdentifier()
    {
        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel */
        $channel = $this->createMock(Channel::class);

        /** @var ShippingService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->createMock(ShippingService::class);
        $service->expects($this->once())
            ->method('getCode')
            ->willReturn('59');

        $generator = new UPSMethodTypeIdentifierGenerator();

        $this->assertEquals('59', $generator->generateIdentifier($channel, $service));
    }
}
