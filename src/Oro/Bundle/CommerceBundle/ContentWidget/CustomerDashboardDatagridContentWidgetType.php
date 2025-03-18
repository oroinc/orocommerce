<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CommerceBundle\Form\Type\CustomerDashboardDatagridSelectType;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Twig\Environment;

/**
 * Type for customer dashboard datagrid widgets.
 */
class CustomerDashboardDatagridContentWidgetType implements ContentWidgetTypeInterface
{
    private int $pointer = 1;

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ManagerInterface $manager,
        private FrontendHelper $frontendHelper
    ) {
    }

    #[\Override]
    public static function getName(): string
    {
        return 'customer_dashboard_datagrid';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.commerce.content_widget_type.customer_dashboard_datagrid.label';
    }

    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory->create(FormType::class)
            ->add('datagrid', CustomerDashboardDatagridSelectType::class, [
                'label' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.label',
                'required' => true,
                'block' => 'options',
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('labels', LocalizedFallbackValueCollectionType::class, [
                'data' => $contentWidget->getLabels()->toArray(),
                'label' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.options.labels.singular_label',
                'tooltip' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.options.labels.tooltip',
                'required' => false,
                'block' => 'options',
                'entry_options'  => [
                    'constraints' => [new Length(['max' => 255])],
                ]
            ])
            ->add('viewAll', RouteChoiceType::class, [
                'required' => false,
                'block' => 'options',
                'label' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.options.view_all.label',
                'tooltip' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.options.view_all.tooltip',
                'placeholder' => 'oro.customer.form.system_page_route.placeholder',
                'options_filter' => ['frontend' => true],
                'menu_name' => 'frontend_menu',
                'name_filter' => '/^oro_\w+(?<!frontend_root)$/'
            ]);
    }

    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $data = $this->getWidgetData($contentWidget);

        return [
            [
                'title' => 'oro.cms.contentwidget.sections.options',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render(
                                '@OroCommerce/CustomerDashboardDatagridContentWidget/options.html.twig',
                                $data
                            )
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $data = $contentWidget->getSettings();
        $data['defaultLabel'] = $contentWidget->getDefaultLabel();
        $data['labels'] = $contentWidget->getLabels();
        $data['pageComponentName'] = \sprintf('%s:%s', $data['datagrid'], $this->pointer++);

        if ($this->frontendHelper->isFrontendRequest()) {
            $config = $this->manager->getConfigurationForGrid($data['datagrid']);
            $data['visible'] = $this->isVisibleContentWidget($config);
        }

        return $data;
    }

    public function isInline(): bool
    {
        return false;
    }

    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }

    private function isVisibleContentWidget(DatagridConfiguration $config): bool
    {
        $rootEntity = $config->getOrmQuery()?->getRootEntity();

        return !empty($rootEntity) && $this->authorizationChecker->isGranted(BasicPermission::VIEW, new $rootEntity());
    }
}
