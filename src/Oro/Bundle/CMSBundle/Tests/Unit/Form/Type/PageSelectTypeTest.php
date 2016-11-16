<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PageSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new PageSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(PageSelectType::NAME, $this->formType->getName());
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
                    $this->assertEquals('oro_cms_page', $options['autocomplete_alias']);
                    $this->assertEquals('oro_cms_page_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'placeholder' => 'oro.cms.page.form.choose'
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }
}
