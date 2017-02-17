<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Model\UrlSet;
use Oro\Bundle\SEOBundle\Tools\Encoder\UrlItemEncoder;
use Oro\Component\SEO\Model\UrlSetInterface;

class UrlSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlSet
     */
    private $urlSet;
    
    protected function setUp()
    {
        $this->urlSet = new UrlSet();
    }

    public function testConstruct()
    {
        $urlItemEncoderReflection = $this->getPropertyReflection('urlItemEncoder');
        $this->assertInstanceOf(UrlItemEncoder::class, $urlItemEncoderReflection->getValue($this->urlSet));

        $fileSizeReflection = $this->getPropertyReflection('fileSize');
        $expectedFilesize = strlen(sprintf('xmlns="%s"', UrlSetInterface::ROOT_NODE_XMLNS)) + 1;
        $this->assertEquals($expectedFilesize, $fileSizeReflection->getValue($this->urlSet));
    }

    public function testAddUrlItem()
    {
        $urlFilesizeReflection = $this->getPropertyReflection('fileSize');
        $oldFilesize = $urlFilesizeReflection->getValue($this->urlSet);
        $urlItem = new UrlItem('http://example.com/', 'daily', 0.5, new \DateTime());
        $addedSuccessfully = $this->urlSet->addUrlItem($urlItem);

        $this->assertTrue($addedSuccessfully);
        $this->assertEquals([$urlItem], $this->urlSet->getUrlItems());
        $urlItemsCountReflection = $this->getPropertyReflection('urlItemsCount');
        $this->assertEquals(1, $urlItemsCountReflection->getValue($this->urlSet));
        $this->assertGreaterThan($oldFilesize, $urlFilesizeReflection->getValue($this->urlSet));
    }

    public function testAddUrlItemWhenUrlsLimitIsReached()
    {
        $urlItemsCountReflection = $this->getPropertyReflection('urlItemsCount');
        $urlItemsCountReflection->setValue($this->urlSet, UrlSetInterface::URLS_LIMIT);
        $urlItem = new UrlItem('http://example.com/', 'daily', 0.5, new \DateTime());
        $addedSuccessfully = $this->urlSet->addUrlItem($urlItem);

        $this->assertFalse($addedSuccessfully);
    }

    public function testAddUrlItemWhenFileSizeIsExceeded()
    {
        $urlFilesizeReflection = $this->getPropertyReflection('fileSize');
        $urlFilesizeReflection->setValue(
            $this->urlSet,
            $urlFilesizeReflection->getValue($this->urlSet) + UrlSetInterface::FILE_SIZE_LIMIT
        );
        $urlItem = new UrlItem('http://example.com/', 'daily', 0.5, new \DateTime());
        $addedSuccessfully = $this->urlSet->addUrlItem($urlItem);

        $this->assertFalse($addedSuccessfully);
    }

    /**
     * @param string $property
     * @return \ReflectionProperty
     */
    private function getPropertyReflection($property)
    {
        $propertyReflection = new \ReflectionProperty(UrlSet::class, $property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection;
    }
}
