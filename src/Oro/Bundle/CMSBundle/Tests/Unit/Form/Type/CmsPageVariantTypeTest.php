<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class CmsPageVariantTypeTest extends FormIntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    PageSelectType::class => new FormStub(PageSelectType::NAME),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(CmsPageVariantType::class);

        $this->assertTrue($form->has('cmsPage'));
        $this->assertTrue($form->has('doNotRenderTitle'));
        $this->assertFalse($form->get('doNotRenderTitle')->getData());
        $this->assertEquals(
            CmsPageContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
    }

    public function testGetBlockPrefix(): void
    {
        $type = new CmsPageVariantType();
        $this->assertEquals('oro_cms_page_variant', $type->getBlockPrefix());
    }
}
