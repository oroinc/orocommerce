<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting one or multiple CMS pages using AJAX autocomplete.
 * Supports both single and multiple selection modes.
 */
class PageSelectionType extends AbstractType
{
    public const OPTION_CONFIGS_DEFAULTS = 'configs_defaults';

    public function __construct(
        protected readonly ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!empty($options['configs']['multiple'])) {
            // Multiple selection: add transformer for "array of entities <-> array of IDs"
            $builder->addModelTransformer(
                new EntitiesToIdsTransformer(
                    $this->doctrine->getManagerForClass(Page::class),
                    Page::class,
                )
            );
        }
        /**
         * For single selection, the parent {@see OroJquerySelect2HiddenType} automatically creates
         * {@see \Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer} based on the 'entity_class' option.
         */
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaultConfigsOptionValue = [
            'result_template_twig' => '@OroCMS/Form/Autocomplete/page/result.html.twig',
            'selection_template_twig' => '@OroCMS/Form/Autocomplete/page/selection.html.twig',
        ];

        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_cms_page_with_slug_and_id',
            'entity_class' => Page::class,
            'configs' => $defaultConfigsOptionValue,
            self::OPTION_CONFIGS_DEFAULTS => $defaultConfigsOptionValue, // see buildView for details
            'placeholder' => 'oro.cms.page.form.choose',
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // We do this in buildView() because the parent type (OroJquerySelect2HiddenType) has its own normalizer for
        // 'configs' option. If we set our own normalizer in configureOptions() - it replaces the parent's normalizer,
        // and we would either lose what the parent does or have to replicate it entirely in our normalizer.

        if (!isset($view->vars['configs']) || !\is_array($view->vars['configs'])) {
            return;
        }

        // Set our defaults only if the user hasn't provided respective values for 'configs'
        $view->vars['configs'] = \array_replace_recursive(
            $options[self::OPTION_CONFIGS_DEFAULTS],
            $view->vars['configs']
        );
    }

    #[\Override]
    public function getParent(): string
    {
        return OroJquerySelect2HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_cms_page_selection';
    }
}
