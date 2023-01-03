<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class CmsPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    PageSelectType::class => new FormStub(PageSelectType::NAME),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(CmsPageVariantType::class);
        $this->assertTrue($form->has('cmsPage'));
        $this->assertEquals(
            CmsPageContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
    }

    public function testGetName()
    {
        $type = new CmsPageVariantType();
        $this->assertEquals(CmsPageVariantType::NAME, $type->getName());
    }

    public function testGetBlockPrefix()
    {
        $type = new CmsPageVariantType();
        $this->assertEquals(CmsPageVariantType::NAME, $type->getBlockPrefix());
    }
}
