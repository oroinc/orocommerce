<?php

namespace Oro\Component\WebCatalog\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\WebCatalog\ChainContentVariantTitleProvider;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;

class ChainContentVariantTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainContentVariantTitleProvider
     */
    protected $chainProvider;

    protected function setUp()
    {
        $this->chainProvider = new ChainContentVariantTitleProvider();
    }

    public function testGetTitle()
    {
        $contentVariant = new ContentVariant();

        /** @var ContentVariantTitleProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(ContentVariantTitleProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $provider
            ->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue('some title'));
        $this->chainProvider->addProvider($provider);
        $this->assertEquals('some title', $this->chainProvider->getTitle($contentVariant));
    }

    public function testGetFirstTitle()
    {
        $contentVariant = new ContentVariant();
        $contentVariants = new ArrayCollection([$contentVariant]);

        /** @var ContentVariantTitleProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(ContentVariantTitleProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $provider
            ->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue('some title'));
        $this->chainProvider->addProvider($provider);
        $this->assertEquals('some title', $this->chainProvider->getFirstTitle($contentVariants));
    }
}
