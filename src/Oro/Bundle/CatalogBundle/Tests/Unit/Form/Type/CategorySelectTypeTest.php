<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CatalogBundle\Form\Type\CategorySelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategorySelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CategorySelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new CategorySelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(CategorySelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('oro_category', $options['autocomplete_alias']);
                    $this->assertEquals('oro_catalog_category_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'placeholder' => 'oro.catalog.category.form.choose'
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }
}
