<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectionType;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageSelectionTypeTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private PageSelectionType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->formType = new PageSelectionType($this->doctrine);
    }

    public function testGetParent(): void
    {
        self::assertEquals(OroJquerySelect2HiddenType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_cms_page_selection', $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve([]);

        self::assertEquals('oro_cms_page_with_slug_and_id', $resolvedOptions['autocomplete_alias']);
        self::assertEquals(Page::class, $resolvedOptions['entity_class']);
        self::assertEquals('oro.cms.page.form.choose', $resolvedOptions['placeholder']);
        self::assertArrayHasKey('configs', $resolvedOptions);
        self::assertEquals(
            '@OroCMS/Form/Autocomplete/page/result.html.twig',
            $resolvedOptions['configs']['result_template_twig']
        );
        self::assertEquals(
            '@OroCMS/Form/Autocomplete/page/selection.html.twig',
            $resolvedOptions['configs']['selection_template_twig']
        );
        self::assertArrayHasKey(PageSelectionType::OPTION_CONFIGS_DEFAULTS, $resolvedOptions);
    }

    public function testBuildFormWithMultipleSelection(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(EntitiesToIdsTransformer::class));

        $this->formType->buildForm($builder, ['configs' => ['multiple' => true]]);
    }

    public function testBuildFormWithSingleSelection(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::never())
            ->method('addModelTransformer');

        $this->formType->buildForm($builder, ['configs' => []]);
    }

    public function testBuildViewMergesConfigsDefaults(): void
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $view->vars['configs'] = [
            'custom_option' => 'custom_value',
            'result_template_twig' => '@Custom/result.html.twig',
        ];

        $options = [
            PageSelectionType::OPTION_CONFIGS_DEFAULTS => [
                'result_template_twig' => '@OroCMS/Form/Autocomplete/page/result.html.twig',
                'selection_template_twig' => '@OroCMS/Form/Autocomplete/page/selection.html.twig',
            ],
        ];

        $this->formType->buildView($view, $form, $options);

        // Custom option should be preserved
        self::assertEquals('custom_value', $view->vars['configs']['custom_option']);
        // User-provided value should override default
        self::assertEquals('@Custom/result.html.twig', $view->vars['configs']['result_template_twig']);
        // Default should be applied when not provided by user
        self::assertEquals(
            '@OroCMS/Form/Autocomplete/page/selection.html.twig',
            $view->vars['configs']['selection_template_twig']
        );
    }

    public function testBuildViewWithNonArrayConfigs(): void
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $view->vars['configs'] = 'not_an_array';

        $options = [
            PageSelectionType::OPTION_CONFIGS_DEFAULTS => [
                'result_template_twig' => '@OroCMS/Form/Autocomplete/page/result.html.twig',
            ],
        ];

        $this->formType->buildView($view, $form, $options);

        // Should not modify configs if it's not an array
        self::assertEquals('not_an_array', $view->vars['configs']);
    }

    public function testBuildViewWithoutConfigs(): void
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();

        $options = [
            PageSelectionType::OPTION_CONFIGS_DEFAULTS => [
                'result_template_twig' => '@OroCMS/Form/Autocomplete/page/result.html.twig',
            ],
        ];

        $this->formType->buildView($view, $form, $options);

        // Should not fail when configs is not set
        self::assertArrayNotHasKey('configs', $view->vars);
    }
}
