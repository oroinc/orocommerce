<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class TextContentVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
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
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
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

    public function testBuildForm(): void
    {
        $form = $this->factory->create(TextContentVariantType::class);

        self::assertTrue($form->has('scopes'));
        self::assertTrue($form->has('content'));
        self::assertTrue($form->has('default'));
        self::assertEquals(
            ['contentStyle' => 'content_style'],
            $form->getConfig()->getOption('error_mapping')
        );
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param TextContentVariant $existingData
     * @param array $submittedData
     * @param TextContentVariant $expectedData
     */
    public function testSubmit(
        TextContentVariant $existingData,
        array $submittedData,
        TextContentVariant $expectedData
    ): void {
        $form = $this->factory->create(TextContentVariantType::class, $existingData);

        self::assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'new entity' => [
                new TextContentVariant(),
                [
                    'scopes' => [],
                    'content' => 'some_title',
                ],
                (new TextContentVariant())
                    ->setContent('some_title')
            ],
            'existing entity' => [
                (new TextContentVariant())
                    ->setContent('some_old_title')
                    ->setContentBlock(new ContentBlock()),
                [
                    'scopes' => [],
                    'content' => 'some_changed_title',
                    'default' => true
                ],
                (new TextContentVariant())
                    ->setContent('some_changed_title')
                    ->setContentBlock(new ContentBlock())
                    ->setDefault(true)
            ],
        ];
    }
}
