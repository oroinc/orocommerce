<?php

namespace Oro\Component\WebCatalog\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\WebCatalog\ChainContentVariantTitleProvider;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ChainContentVariantTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantTitleProviderInterface
     */
    protected $provider;

    /**
     * @var ChainContentVariantTitleProvider
     */
    protected $chainProvider;

    /**
     * @var ContentVariant
     */
    protected $contentVariant;

    /**
     * @var ArrayCollection|ContentVariantInterface[]
     */
    protected $contentVariants;

    protected function setUp()
    {
        $this->chainProvider = new ChainContentVariantTitleProvider();
        $this->contentVariant = new ContentVariant();
        $this->contentVariants = new ArrayCollection();
        $this->contentVariants->add(new ContentVariant());
    }

    public function testGetTitle()
    {
        $this->provider = $this
            ->getMockBuilder(ContentVariantTitleProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider
            ->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue('some title'));
        $this->chainProvider->addProvider($this->provider);
        $this->assertEquals('some title', $this->chainProvider->getTitle($this->contentVariant));
    }

    public function testGetFirstTitle()
    {
        $this->provider = $this
            ->getMockBuilder(ContentVariantTitleProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider
            ->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue('some title'));
        $this->chainProvider->addProvider($this->provider);
        $this->assertEquals('some title', $this->chainProvider->getFirstTitle($this->contentVariants));
    }
}
