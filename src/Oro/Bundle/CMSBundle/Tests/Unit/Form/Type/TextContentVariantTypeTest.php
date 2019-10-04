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
    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    WYSIWYGType::class => new WYSIWYGType(),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(TextContentVariantType::class);

        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('content'));
        $this->assertTrue($form->has('default'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param ContentBlock $existingData
     * @param array $submittedData
     * @param ContentBlock $expectedData
     */
    public function testSubmit($existingData, $submittedData, $expectedData)
    {
        $form = $this->factory->create(TextContentVariantType::class, $existingData);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
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
