<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Provider\SluggableUrlProviderInterface;
use Oro\Bundle\RedirectBundle\Provider\UrlProviderFactory;

class UrlProviderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetKnown()
    {
        /** @var SluggableUrlProviderInterface $provider */
        $provider = $this->createMock(SluggableUrlProviderInterface::class);
        $type = 'test';
        $factory = new UrlProviderFactory($type);
        $factory->registerProvider($type, $provider);
        $this->assertSame($provider, $factory->get());
    }

    public function testGetUnknown()
    {
        /** @var SluggableUrlProviderInterface $provider */
        $provider = $this->createMock(SluggableUrlProviderInterface::class);
        $type = 'test';
        $factory = new UrlProviderFactory('some_type');
        $factory->registerProvider($type, $provider);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There is no UrlProvider registered for type some_type. Known types: test');

        $factory->get();
    }
}
