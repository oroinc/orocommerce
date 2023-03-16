<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Asset\Packages as AssetHelper;

/**
 * @method \PHPUnit\Framework\MockObject\MockObject createMock(string $originalClassName)
 */
trait WysiwygAwareTestTrait
{
    private function createWysiwygType(): WYSIWYGType
    {
        /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject $htmlTagProvider */
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        /** @var HTMLPurifierScopeProvider|\PHPUnit\Framework\MockObject\MockObject $purifierScopeProvider */
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        /** @var DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject $twigTagsConverter */
        $twigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $twigTagsConverter
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $twigTagsConverter
            ->method('convertToTwigTags')
            ->willReturnArgument(0);
        $assetHelper = $this->createMock(AssetHelper::class);
        $assetHelper
            ->expects(self::any())
            ->method('getUrl')
            ->willReturnArgument(0);
        $entityProvider = $this->createMock(EntityProvider::class);
        $entityProvider
            ->expects(self::any())
            ->method('getEntity')
            ->willReturn([
                'name' => 'TestEntityClassName',
                'label' => 'TestEntityLabel',
                'plural_label' => 'TestEntityPluralLabel',
                'icon' => 'fa-icon'
            ]);

        $eventSubscriber = new DigitalAssetTwigTagsEventSubscriber($twigTagsConverter);

        $formType = new WYSIWYGType($htmlTagProvider, $purifierScopeProvider, $twigTagsConverter);
        $formType->setDigitalAssetTwigTagsEventSubscriber($eventSubscriber);
        $formType->setAssetHelper($assetHelper);
        $formType->setEntityProvider($entityProvider);

        return $formType;
    }
}
