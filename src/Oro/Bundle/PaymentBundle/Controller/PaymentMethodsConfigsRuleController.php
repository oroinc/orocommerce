<?php

namespace Oro\Bundle\PaymentBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Form\Handler\PaymentMethodsConfigsRuleHandler;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Payment Methods Configs Rule Controller
 */
class PaymentMethodsConfigsRuleController extends AbstractController
{
    private array $addMethodWidgetUpdateFlags = [PaymentMethodsConfigsRuleHandler::UPDATE_FLAG];

    public function addUpdateFlagToAddMethodWidget(string $addMethodWidgetUpdateFlags): void
    {
        if (false === \in_array($addMethodWidgetUpdateFlags, $this->addMethodWidgetUpdateFlags, true)) {
            $this->addMethodWidgetUpdateFlags[] = $addMethodWidgetUpdateFlags;
        }
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_payment_methods_configs_rule_index')]
    #[Template]
    #[AclAncestor('oro_payment_methods_configs_rule_view')]
    public function indexAction()
    {
        return [
            'entity_class' => PaymentMethodsConfigsRule::class
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    #[Route(path: '/create', name: 'oro_payment_methods_configs_rule_create')]
    #[Template('@OroPayment/PaymentMethodsConfigsRule/update.html.twig')]
    #[Acl(
        id: 'oro_payment_methods_configs_rule_create',
        type: 'entity',
        class: PaymentMethodsConfigsRule::class,
        permission: 'CREATE'
    )]
    public function createAction(Request $request)
    {
        return $this->update(new PaymentMethodsConfigsRule(), $request);
    }

    /**
     * @param PaymentMethodsConfigsRule $paymentMethodsConfigsRule
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_payment_methods_configs_rule_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(
        id: 'oro_payment_methods_configs_rule_view',
        type: 'entity',
        class: PaymentMethodsConfigsRule::class,
        permission: 'VIEW'
    )]
    public function viewAction(PaymentMethodsConfigsRule $paymentMethodsConfigsRule)
    {
        return [
            'entity' => $paymentMethodsConfigsRule,
        ];
    }

    /**
     * @param Request $request
     * @param PaymentMethodsConfigsRule $entity
     * @return array
     */
    #[Route(path: '/update/{id}', name: 'oro_payment_methods_configs_rule_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(
        id: 'oro_payment_methods_configs_rule_update',
        type: 'entity',
        class: PaymentMethodsConfigsRule::class,
        permission: 'EDIT'
    )]
    public function updateAction(Request $request, PaymentMethodsConfigsRule $entity)
    {
        return $this->update($entity, $request);
    }

    /**
     * @param PaymentMethodsConfigsRule $entity
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(PaymentMethodsConfigsRule $entity, Request $request)
    {
        $form = $this->createForm(PaymentMethodsConfigsRuleType::class);
        if ($this->container->get(PaymentMethodsConfigsRuleHandler::class)->process($form, $entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.payment.controller.rule.saved.message')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        if ($request->get(PaymentMethodsConfigsRuleHandler::UPDATE_FLAG, false)) {
            // take different form due to JS validation should be shown even in case
            // when it was not validated on backend
            $form = $this->createForm(PaymentMethodsConfigsRuleType::class, $form->getData());
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
            'addMethodWidgetUpdateFlags' => $this->addMethodWidgetUpdateFlags
        ];
    }

    /**
     *
     * @param string $gridName
     * @param string $actionName
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/{gridName}/massAction/{actionName}', name: 'oro_payment_methods_configs_massaction')]
    #[Acl(
        id: 'oro_payment_methods_configs_update',
        type: 'entity',
        class: PaymentMethodsConfigsRule::class,
        permission: 'EDIT'
    )]
    #[CsrfProtection()]
    public function markMassAction($gridName, $actionName, Request $request)
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
                TranslatorInterface::class,
                Router::class,
                PaymentMethodsConfigsRuleHandler::class,
                MassActionDispatcher::class,
            ]
        );
    }
}
