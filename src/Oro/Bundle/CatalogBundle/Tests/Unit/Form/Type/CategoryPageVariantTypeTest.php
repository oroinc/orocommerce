<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;

class CategoryPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CategoryPageVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->type = new CategoryPageVariantType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CategoryTreeType::NAME => new FormStub(CategoryTreeType::NAME),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null);
        $this->assertTrue($form->has('categoryPageCategory'));
        $this->assertEquals(
            CategoryPageContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CategoryPageVariantType::NAME, $this->type->getBlockPrefix());
    }
}
