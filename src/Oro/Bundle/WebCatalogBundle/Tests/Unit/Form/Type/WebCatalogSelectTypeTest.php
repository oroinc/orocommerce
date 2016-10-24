<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var WebCatalogSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new WebCatalogSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(WebCatalogSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('oro_web_catalog', $options['autocomplete_alias']);
                    $this->assertEquals('oro_web_catalog_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'placeholder' => 'oro.webcatalog.form.choose'
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }
}
