<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2TextTagType;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents Search Term entity form.
 */
class SearchTermType extends AbstractType
{
    public function __construct(
        private string $phraseDelimiter,
        private EventSubscriberInterface $disableFieldsEventSubscriber
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'phrases',
                Select2TextTagType::class,
                [
                    'required' => true,
                    'configs' => [
                        'separator' => $this->phraseDelimiter,
                        'minimumInputLength' => 1,
                        'selectOnBlur' => true,
                    ],
                ]
            )
            ->add(
                'partialMatch',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::class,
                [
                    'required' => false,
                    'entry_options' => [
                        'scope_type' => 'website_search_term',
                    ],
                    'block_prefix' => 'oro_website_search_term_scopes',
                ]
            )
            ->add(
                'actionType',
                ChoiceType::class,
                [
                    'required' => true,
                    'choices' => [
                        'oro.websitesearchterm.searchterm.action_type.choices.modify.label' => 'modify',
                        'oro.websitesearchterm.searchterm.action_type.choices.redirect.label' => 'redirect',
                    ],
                ]
            )
            ->add(
                'modifyActionType',
                ChoiceType::class,
                [
                    'required' => true,
                    'choices' => [
                        'oro.websitesearchterm.searchterm.modify_action_type.choices.original_results.label' =>
                            'original_results',
                    ],
                ]
            )
            ->add(
                'redirectActionType',
                ChoiceType::class,
                [
                    'required' => true,
                    'choices' => [
                        'oro.websitesearchterm.searchterm.redirect_action_type.choices.system_page.label' =>
                            'system_page',
                        'oro.websitesearchterm.searchterm.redirect_action_type.choices.uri.label' => 'uri',
                    ],
                ]
            )
            ->add(
                'redirectUri',
                TextType::class,
                [
                    'required' => true,
                ]
            )
            ->add(
                'redirectSystemPage',
                RouteChoiceType::class,
                [
                    'required' => true,
                    'placeholder' => 'oro.websitesearchterm.searchterm.redirect_system_page.placeholder',
                    'options_filter' => [
                        'frontend' => true,
                    ],
                    'menu_name' => 'frontend_menu',
                ]
            )
            ->add(
                'redirect301',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            );

        $builder->addEventSubscriber($this->disableFieldsEventSubscriber);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view['scopes']->vars['phraseDelimiter'] = $this->phraseDelimiter;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('disable_fields_if')
            ->allowedTypes('string[]')
            ->default(function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'modifyActionType' => 'data.actionType != "modify"',
                        'redirectActionType' => 'data.actionType != "redirect"',
                        'redirect301' => 'data.actionType != "redirect" || data.redirectActionType == "uri"',
                        'redirectUri' => 'data.actionType != "redirect" || data.redirectActionType != "uri"',
                        'redirectSystemPage' => 'data.actionType != "redirect" || '
                            . 'data.redirectActionType != "system_page"',
                    ];
            });
    }
}
