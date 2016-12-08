<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeNameFiller;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub\ContentNode;
use Oro\Component\WebCatalog\ChainContentVariantTitleProvider;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;

class ContentNodeNameFillerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContentVariantTitleProviderInterface
     */
    protected $contentVariantTitleProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContentNodeNameFiller
     */
    protected $contentNodeNameFiller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContentNode
     */
    protected $contentNode;

    protected function setUp()
    {
        $this->contentVariantTitleProvider = $this
            ->getMockBuilder(ChainContentVariantTitleProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contentNode = new ContentNode();
        $this->contentNodeNameFiller = new ContentNodeNameFiller($this->contentVariantTitleProvider);
    }

    public function testFillNameWithPresetName()
    {
        $this->contentNode->setName('something');
        $this->contentNodeNameFiller->fillName($this->contentNode);
        $this->assertEquals('something', $this->contentNode->getName());
    }

    public function testFillNameWithDefaultTitleUse()
    {
        $this->contentNode->setDefaultTitle((new LocalizedFallbackValue())->setString('some title'));
        $this->contentNodeNameFiller->fillName($this->contentNode);
        $this->assertEquals('some title', $this->contentNode->getName());
    }

    public function testFillNameWithNonDefaultTitleUse()
    {
        $localization = new Localization();
        $localization->setName('de');

        $localizedValue = new LocalizedFallbackValue();
        $localizedValue->setString('some title');
        $localizedValue->setLocalization($localization);
        $this->contentNode->addTitle($localizedValue);
        $this->contentNodeNameFiller->fillName($this->contentNode);
        $this->assertEquals('some title', $this->contentNode->getName());
    }

    public function testFillNameWithContentVariantsTitlesUse()
    {
        $this->contentVariantTitleProvider
            ->expects($this->once())
            ->method('getFirstTitle')
            ->will($this->returnValue('another title'));

        $this->contentNodeNameFiller->fillName($this->contentNode);
        $this->assertEquals('another title', $this->contentNode->getName());
    }
}
