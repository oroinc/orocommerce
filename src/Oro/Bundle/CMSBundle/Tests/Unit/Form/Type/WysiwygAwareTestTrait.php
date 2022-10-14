<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Asset\Packages as AssetHelper;

trait WysiwygAwareTestTrait
{
    private function createWysiwygType(): WYSIWYGType
    {
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $twigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $twigTagsConverter->expects($this->any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $twigTagsConverter->expects($this->any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);
        $assetHelper = $this->createMock(AssetHelper::class);
        $assetHelper->expects(self::any())
            ->method('getUrl')
            ->willReturnArgument(0);

        $eventSubscriber = new DigitalAssetTwigTagsEventSubscriber($twigTagsConverter);

        return new WYSIWYGType($htmlTagProvider, $purifierScopeProvider, $eventSubscriber, $assetHelper);
    }
}
