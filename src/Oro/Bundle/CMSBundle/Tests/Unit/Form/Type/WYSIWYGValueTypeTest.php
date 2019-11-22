<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
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
        /** @var HTMLPurifierScopeProvider|\PHPUnit\Framework\MockObject\MockObject $purifierScopeProvider */
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject $htmlTagProvider */
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);

        return [
            new PreloadedExtension(
                [
                    WYSIWYGType::class => new WYSIWYGType($htmlTagProvider, $purifierScopeProvider),
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
                'field' => 'wysiwyg'
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
