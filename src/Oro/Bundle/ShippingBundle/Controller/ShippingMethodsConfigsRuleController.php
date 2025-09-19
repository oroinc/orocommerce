<?php

namespace Oro\Bundle\ShippingBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Form\Handler\ShippingMethodsConfigsRuleHandler;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Shipping Methods Configs Rule Controller
 */
class ShippingMethodsConfigsRuleController extends AbstractController
{
    private array $addMethodWidgetUpdateFlags = [ShippingMethodsConfigsRuleHandler::UPDATE_FLAG];

    public function addUpdateFlagToAddMethodWidget(string $addMethodWidgetUpdateFlags): void
    {
        if (!\in_array($addMethodWidgetUpdateFlags, $this->addMethodWidgetUpdateFlags, true)) {
            $this->addMethodWidgetUpdateFlags[] = $addMethodWidgetUpdateFlags;
        }
    }

    #[Route(path: '/', name: 'oro_shipping_methods_configs_rule_index')]
    #[Template('@OroShipping/ShippingMethodsConfigsRule/index.html.twig')]
    #[AclAncestor('oro_shipping_methods_configs_rule_view')]
    public function indexAction(): array
    {
        return ['entity_class' => ShippingMethodsConfigsRule::class];
    }

    #[Route(path: '/create', name: 'oro_shipping_methods_configs_rule_create')]
    #[Template('@OroShipping/ShippingMethodsConfigsRule/update.html.twig')]
    #[Acl(
        id: 'oro_shipping_methods_configs_rule_create',
        type: 'entity',
        class: ShippingMethodsConfigsRule::class,
        permission: 'CREATE'
    )]
    public function createAction(Request $request): array|RedirectResponse
    {
        return $this->update(new ShippingMethodsConfigsRule(), $request);
    }

    #[Route(path: '/view/{id}', name: 'oro_shipping_methods_configs_rule_view', requirements: ['id' => '\d+'])]
    #[Acl(
        id: 'oro_shipping_methods_configs_rule_view',
        type: 'entity',
        class: ShippingMethodsConfigsRule::class,
        permission: 'VIEW'
    )]
    public function viewAction(ShippingMethodsConfigsRule $shippingRule): Response
    {
        $organizationProvider = $this->container->get(ShippingMethodOrganizationProvider::class);
        $previousOrganization = $organizationProvider->getOrganization();
        $organizationProvider->setOrganization($shippingRule->getOrganization());
        try {
            return $this->render(
                '@OroShipping/ShippingMethodsConfigsRule/view.html.twig',
                ['entity' => $shippingRule]
            );
        } finally {
            $organizationProvider->setOrganization($previousOrganization);
        }
    }

    #[Route(path: '/update/{id}', name: 'oro_shipping_methods_configs_rule_update', requirements: ['id' => '\d+'])]
    #[Template('@OroShipping/ShippingMethodsConfigsRule/update.html.twig')]
    #[Acl(
        id: 'oro_shipping_methods_configs_rule_update',
        type: 'entity',
        class: ShippingMethodsConfigsRule::class,
        permission: 'EDIT'
    )]
    public function updateAction(Request $request, ShippingMethodsConfigsRule $entity): array|RedirectResponse
    {
        return $this->update($entity, $request);
    }

    protected function update(ShippingMethodsConfigsRule $entity, Request $request): array|RedirectResponse
    {
        $form = $this->createForm(ShippingMethodsConfigsRuleType::class);
        if ($this->container->get(ShippingMethodsConfigsRuleHandler::class)->process($form, $entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.shipping.controller.rule.saved.message')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        if ($request->get(ShippingMethodsConfigsRuleHandler::UPDATE_FLAG, false)) {
            // take different form due to JS validation should be shown even in case
            // when it was not validated on backend
            $form = $this->createForm(ShippingMethodsConfigsRuleType::class, $form->getData());
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
            'addMethodWidgetUpdateFlags' => $this->addMethodWidgetUpdateFlags,
            'addMethodWidgetInFocus' => $request->get(ShippingMethodsConfigsRuleHandler::UPDATE_FLAG, false)
        ];
    }

    #[Route(path: '/{gridName}/massAction/{actionName}', name: 'oro_status_shipping_rule_massaction')]
    #[Acl(
        id: 'oro_shipping_methods_configs_rule_update',
        type: 'entity',
        class: ShippingMethodsConfigsRule::class,
        permission: 'EDIT'
    )]
    #[CsrfProtection()]
    public function markMassAction(string $gridName, string $actionName, Request $request): JsonResponse
    {
        $massActionDispatcher = $this->container->get(MassActionDispatcher::class);

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ShippingMethodOrganizationProvider::class,
                ShippingMethodsConfigsRuleHandler::class,
                TranslatorInterface::class,
                Router::class,
                MassActionDispatcher::class,
            ]
        );
    }
}
