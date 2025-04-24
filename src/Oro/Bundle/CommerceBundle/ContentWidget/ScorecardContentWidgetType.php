<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ScorecardsRegistryInterface;
use Oro\Bundle\CommerceBundle\Form\Type\ScorecardSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Twig\Environment;

/**
 * Type for scorecard widgets.
 */
class ScorecardContentWidgetType implements ContentWidgetTypeInterface
{
    public function __construct(
        private readonly ScorecardsRegistryInterface $scorecardsRegistry
    ) {
    }

    #[\Override]
    public static function getName(): string
    {
        return 'scorecard';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.commerce.content_widget_type.scorecard.label';
    }

    #[\Override]
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory->create(FormType::class)
            ->add('scorecard', ScorecardSelectType::class, [
                'label' => 'oro.commerce.content_widget_type.scorecard.label',
                'required' => true,
                'block' => 'options',
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('labels', LocalizedFallbackValueCollectionType::class, [
                'data' => $contentWidget->getLabels()->toArray(),
                'label' => 'oro.commerce.content_widget_type.scorecard.options.labels.singular_label',
                'tooltip' => 'oro.commerce.content_widget_type.scorecard.options.labels.tooltip',
                'required' => false,
                'block' => 'options',
                'entry_options'  => [
                    'constraints' => [new Length(['max' => 255])],
                ]
            ])
            ->add('link', RouteChoiceType::class, [
                'required' => false,
                'block' => 'options',
                'label' => 'oro.commerce.content_widget_type.scorecard.options.link.label',
                'tooltip' => 'oro.commerce.content_widget_type.scorecard.options.link.tooltip',
                'placeholder' => 'oro.customer.form.system_page_route.placeholder',
                'options_filter' => ['frontend' => true],
                'menu_name' => 'frontend_menu',
                'name_filter' => '/^oro_\w+(?<!frontend_root)$/'
            ]);
    }

    #[\Override]
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $data = $this->getWidgetData($contentWidget);

        return [
            [
                'title' => 'oro.cms.contentwidget.sections.options',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render('@OroCommerce/ScorecardContentWidget/options.html.twig', $data)
                        ]
                    ]
                ]
            ]
        ];
    }

    #[\Override]
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $data = $contentWidget->getSettings();
        $data['defaultLabel'] = $contentWidget->getDefaultLabel();
        $data['labels'] = $contentWidget->getLabels();

        $provider = $this->scorecardsRegistry->getProviderByName($data['scorecard']);
        $data['visible'] = $provider?->isVisible() ?? false;
        $data['metric'] = null;

        if ($data['visible']) {
            $data['metric']['data'] = $provider->getData();
            $data['visible'] = $data['metric']['data'] !== null;
        }

        return $data;
    }

    #[\Override]
    public function isInline(): bool
    {
        return false;
    }

    #[\Override]
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }
}
