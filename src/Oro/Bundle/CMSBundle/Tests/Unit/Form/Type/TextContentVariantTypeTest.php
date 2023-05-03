<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class TextContentVariantTypeTest extends FormIntegrationTestCase
{
    use WysiwygAwareTestTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    WYSIWYGType::class => $this->createWysiwygType(),
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
