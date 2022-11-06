<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugWithRedirectTypeTest extends FormIntegrationTestCase
{
    /** @var ConfirmSlugChangeFormHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $confirmSlugChangeFormHelper;

    /** @var LocalizedSlugWithRedirectType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmSlugChangeFormHelper = $this->createMock(ConfirmSlugChangeFormHelper::class);

        $this->formType = new LocalizedSlugWithRedirectType($this->confirmSlugChangeFormHelper);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugWithRedirectType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    LocalizedSlugWithRedirectType::SLUG_PROTOTYPES_FIELD_NAME,
                    LocalizedSlugType::class,
                    [
                        'required' => false,
                        'entry_options' => ['constraints' => [new UrlSafe(['allowSlashes' => true])]],
                        'label' => false,
                        'source_field' => 'field',
                        'slug_suggestion_enabled' => true,
                    ]
                ],
                [
                    LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME,
                    CheckboxType::class,
                    [
                        'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                        'data' => true,
                    ]
                ]
            )
            ->willReturnSelf();

        $this->formType->buildForm(
            $builder,
            ['source_field' => 'field', 'slug_suggestion_enabled' => true, 'allow_slashes' => true]
        );
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(function (array $options) {
                    $this->assertEquals(SlugPrototypesWithRedirect::class, $options['data_class']);
                    $this->assertTrue($options['slug_suggestion_enabled']);
                    $this->assertTrue($options['create_redirect_enabled']);
                    $this->assertEquals('oro.redirect.slug_prototypes.tooltip', $options['tooltip']);

                    return true;
                })
            );
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildViewWhenGetChangedSlugsUrlOptionsIsNull()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['get_changed_slugs_url' => null];

        $this->confirmSlugChangeFormHelper->expects($this->once())
            ->method('addConfirmSlugChangeOptionsLocalized')
            ->with($view, $form, $options);

        $this->formType->buildView($view, $form, $options);

        $this->assertEquals(true, $view->vars['confirm_slug_change_component_options']['disabled']);
        $this->assertArrayNotHasKey('changedSlugsUrl', $view->vars['confirm_slug_change_component_options']);
    }

    public function testBuildViewWhenGetChangedSlugsUrlOptionsIsNotNull()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $changedSlugsUrl = '/some/action/3';
        $options = ['get_changed_slugs_url' => $changedSlugsUrl];

        $this->confirmSlugChangeFormHelper->expects($this->once())
            ->method('addConfirmSlugChangeOptionsLocalized')
            ->with($view, $form, $options);

        $this->formType->buildView($view, $form, $options);

        $this->assertEquals($changedSlugsUrl, $view->vars['confirm_slug_change_component_options']['changedSlugsUrl']);
    }
}
