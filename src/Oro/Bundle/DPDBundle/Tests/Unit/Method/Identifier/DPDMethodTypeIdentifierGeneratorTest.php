<?php

namespace Oro\Bundle\DPDBundle\Tests\Units\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGenerator;

class DPDMethodTypeIdentifierGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateIdentifier()
    {
        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);

        /** @var ShippingService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->createMock(ShippingService::class);
        $service->expects($this->once())
            ->method('getCode')
            ->willReturn('59');

        $generator = new DPDMethodTypeIdentifierGenerator();

        $this->assertEquals('59', $generator->generateIdentifier($channel, $service));
    }
}
