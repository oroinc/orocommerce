<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionSelectType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PromotionSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new PromotionSelectType();
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
                    $this->assertFalse($options['create_enabled']);
                    $this->assertEquals(PromotionType::class, $options['autocomplete_alias']);
                    $this->assertEquals('oro_promotion_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'placeholder' => 'oro.promotion.form.choose'
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->configureOptions($resolver);
    }
}
