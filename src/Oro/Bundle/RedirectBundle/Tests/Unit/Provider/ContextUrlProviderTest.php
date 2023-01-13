<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderInterface;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ContextUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testProvider()
    {
        $type = 'test';
        $data = ['id' => 1];
        $url = '/test/url';

        $urlProvider = $this->createMock(ContextUrlProviderInterface::class);
        $urlProvider->expects($this->once())
            ->method('getUrl')
            ->with($data)
            ->willReturn($url);

        $container = TestContainerBuilder::create()
            ->add($type, $urlProvider)
            ->getContainer($this);
        $provider = new ContextUrlProviderRegistry($container);

        $this->assertEquals($url, $provider->getUrl($type, $data));
        $this->assertNull($provider->getUrl('unknown', $data));
    }
}
