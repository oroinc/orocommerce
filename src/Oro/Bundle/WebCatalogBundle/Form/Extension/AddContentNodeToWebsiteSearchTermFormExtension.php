<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Form\Extension;

use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeFromWebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "content_node" choice to redirect action type and "redirectContentNode" field to {@see SearchTermType} form.
 */
class AddContentNodeToWebsiteSearchTermFormExtension extends AbstractTypeExtension
{
    public function __construct(private WebCatalogProvider $webCatalogProvider)
    {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [SearchTermType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $redirectTypeForm = $builder->get('redirectActionType');
        $redirectTypeFormConfig = $redirectTypeForm->getFormConfig();
        $redirectChoices = [
                'oro.websitesearchterm.searchterm.redirect_action_type.choices.content_node.label' => 'content_node',
            ] + $redirectTypeFormConfig->getOption('choices');

        $builder
            ->add(
                'redirectActionType',
                $redirectTypeFormConfig->getType()->getInnerType()::class,
                ['choices' => $redirectChoices] + $redirectTypeFormConfig->getOptions()
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $searchTerm = $event->getData();
            $webCatalog = $searchTerm?->getRedirectContentNode()?->getWebCatalog() ??
                $this->webCatalogProvider->getWebCatalog();

            $form
                ->add(
                    'redirectWebCatalog',
                    WebCatalogSelectType::class,
                    [
                        'data' => $webCatalog,
                        'required' => true,
                        'mapped' => false,
                        'error_bubbling' => false,
                        'create_enabled' => false,
                    ]
                )
                ->add(
                    'redirectContentNode',
                    ContentNodeFromWebCatalogSelectType::class,
                    array_merge(
                        [
                            'required' => true,
                            'error_bubbling' => false,
                        ],
                        $webCatalog instanceof WebCatalog ? ['web_catalog' => $webCatalog] : []
                    )
                );
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_fields_if', function (Options $options, $previousValue) {
                return (array)$previousValue + [
                        'redirectContentNode' => 'data.actionType != "redirect" || '
                            . 'data.redirectActionType != "content_node"',
                    ];
            });
    }
}
