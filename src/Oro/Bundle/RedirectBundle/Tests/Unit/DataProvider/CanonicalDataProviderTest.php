<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DataProvider;

use Oro\Bundle\RedirectBundle\DataProvider\CanonicalDataProvider;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;

class CanonicalDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUrl()
    {
        /** @var CanonicalUrlGenerator|\PHPUnit_Framework_MockObject_MockObject $canonicalUrlGenerator */
        $canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $canonicalUrlGenerator->expects($this->any())
            ->method('getUrl')
            ->willReturn('some-url');
        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data */
        $data = $this->createMock(SluggableInterface::class);
        $canonicalDataProvider = new CanonicalDataProvider($canonicalUrlGenerator);

        $this->assertEquals('some-url', $canonicalDataProvider->getUrl($data));
    }
}
