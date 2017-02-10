<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;

class CategoryPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CmsPageVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->type = new CmsPageVariantType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    PageSelectType::NAME => new FormStub(PageSelectType::NAME),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null);
        $this->assertTrue($form->has('cmsPage'));
        $this->assertEquals(
            CmsPageContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
    }

    public function testGetName()
    {
        $this->assertEquals(CmsPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CmsPageVariantType::NAME, $this->type->getBlockPrefix());
    }
}
