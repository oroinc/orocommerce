<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WYSIWYGValueTypeTest extends FormIntegrationTestCase
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
                    WYSIWYGType::class => $this->createWysiwygType(),
                ],
                []
            )
        ];
    }

    public function testConfigureOptions(): void
    {
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
