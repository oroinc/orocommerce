<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderInterface;

class ContextUrlProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testProvider()
    {
        $type = 'test';
        $data = ['id' => 1];
        $url = '/test/url';

        $provider = new ContextUrlProviderRegistry();

        /** @var ContextUrlProviderInterface|\PHPUnit_Framework_MockObject_MockObject $urlProvider */
        $urlProvider = $this->createMock(ContextUrlProviderInterface::class);
        $urlProvider->expects($this->once())
            ->method('getUrl')
            ->with($data)
            ->willReturn($url);

        $provider->registerProvider($urlProvider, $type);
        $this->assertEquals($url, $provider->getUrl($type, $data));
        $this->assertNull($provider->getUrl('unknown', $data));
    }
}
