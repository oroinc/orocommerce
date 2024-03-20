<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DataProvider;

use Oro\Bundle\RedirectBundle\DataProvider\CanonicalDataProvider;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanonicalDataProviderTest extends TestCase
{
    private CanonicalUrlGenerator|MockObject $canonicalUrlGenerator;

    private CanonicalDataProvider $canonicalDataProvider;

    protected function setUp(): void
    {
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->canonicalDataProvider = new CanonicalDataProvider($this->canonicalUrlGenerator);
    }

    public function testGetUrl(): void
    {
        $url = 'http://localhost/some-url';
        $data = $this->createMock(SluggableInterface::class);

        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getUrl')
            ->with($data)
            ->willReturn($url);

        self::assertEquals($url, $this->canonicalDataProvider->getUrl($data));
    }

    public function getHomePageUrl(): void
    {
        $url = 'http://localhost/';
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with('/')
            ->willReturn($url);

        self::assertEquals($url, $this->canonicalDataProvider->getHomePageUrl());
    }
}
