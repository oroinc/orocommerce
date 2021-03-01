<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WYSIWYGValueTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);

        return [
            new PreloadedExtension(
                [
                    WYSIWYGType::class => new WYSIWYGType(
                        $htmlTagProvider,
                        $purifierScopeProvider,
                        $digitalAssetTwigTagsConverter
                    )
                ],
                []
            )
        ];
    }

    public function testConfigureOptions(): void
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'field' => 'wysiwyg',
                'entity_class' => null,
            ])
            ->willReturnSelf();

        $type = new WYSIWYGValueType();
        $type->configureOptions($resolver);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(WYSIWYGValueType::class);
        $form->submit(['wysiwyg' => '<h1>Heading text</h1><p>Body text</p>']);
        $this->assertEquals(['wysiwyg' => '<h1>Heading text</h1><p>Body text</p>'], $form->getData());
    }
}
