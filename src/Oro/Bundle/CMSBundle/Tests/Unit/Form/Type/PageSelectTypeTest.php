<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\CMSBundle\Form\Type\PageType;
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
    protected function setUp(): void
    {
        $this->formType = new PageSelectType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(PageType::class, $options['autocomplete_alias']);
                    $this->assertEquals('oro_cms_page_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'placeholder' => 'oro.cms.page.form.choose'
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->configureOptions($resolver);
    }
}
