<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DataProvider;

use Oro\Bundle\RedirectBundle\DataProvider\CanonicalDataProvider;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;

class CanonicalDataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUrl()
    {
        /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject $canonicalUrlGenerator */
        $canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $canonicalUrlGenerator->expects($this->any())
            ->method('getUrl')
            ->willReturn('some-url');
        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $data */
        $data = $this->createMock(SluggableInterface::class);
        $canonicalDataProvider = new CanonicalDataProvider($canonicalUrlGenerator);

        $this->assertEquals('some-url', $canonicalDataProvider->getUrl($data));
    }
}
