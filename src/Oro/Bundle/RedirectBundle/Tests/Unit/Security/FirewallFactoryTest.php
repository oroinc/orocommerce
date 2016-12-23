<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Security;

use Oro\Bundle\RedirectBundle\Security\FirewallFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall as FrameworkFirewall;
use Symfony\Component\Security\Http\FirewallMapInterface;

class FirewallFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        /** @var FirewallMapInterface|\PHPUnit_Framework_MockObject_MockObject $map */
        $map = $this->createMock(FirewallMapInterface::class);

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $factory = new FirewallFactory();
        $this->assertInstanceOf(FrameworkFirewall::class, $factory->create($map, $dispatcher));
    }
}
