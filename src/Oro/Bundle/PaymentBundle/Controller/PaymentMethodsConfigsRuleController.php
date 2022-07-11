<?php

namespace Oro\Bundle\PaymentBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Form\Handler\PaymentMethodsConfigsRuleHandler;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Route("/", name="oro_payment_methods_configs_rule_index")
     * @Template
     * @AclAncestor("oro_payment_methods_configs_rule_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => PaymentMethodsConfigsRule::class
        ];
    }

    /**
     * @Route("/create", name="oro_payment_methods_configs_rule_create")
     * @Template("@OroPayment/PaymentMethodsConfigsRule/update.html.twig")
     * @Acl(
     *     id="oro_payment_methods_configs_rule_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroPaymentBundle:PaymentMethodsConfigsRule"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request)
    {
        return $this->update(new PaymentMethodsConfigsRule(), $request);
    }

    /**
     * @Route("/view/{id}", name="oro_payment_methods_configs_rule_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_payment_methods_configs_rule_view",
     *      type="entity",
     *      class="OroPaymentBundle:PaymentMethodsConfigsRule",
     *      permission="VIEW"
     * )
     *
     * @param PaymentMethodsConfigsRule $paymentMethodsConfigsRule
     *
     * @return array
     */
    public function viewAction(PaymentMethodsConfigsRule $paymentMethodsConfigsRule)
    {
        return [
            'entity' => $paymentMethodsConfigsRule,
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_payment_methods_configs_rule_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="oro_payment_methods_configs_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroPaymentBundle:PaymentMethodsConfigsRule"
     * )
     * @param Request $request
     * @param PaymentMethodsConfigsRule $entity
     *
     * @return array
     */
    public function updateAction(Request $request, PaymentMethodsConfigsRule $entity)
    {
        return $this->update($entity, $request);
    }

    /**
     * @param PaymentMethodsConfigsRule $entity
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function update(PaymentMethodsConfigsRule $entity, Request $request)
    {
        $form = $this->createForm(PaymentMethodsConfigsRuleType::class);
        if ($this->get(PaymentMethodsConfigsRuleHandler::class)->process($form, $entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.payment.controller.rule.saved.message')
            );

            return $this->get(Router::class)->redirect($entity);
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
     * @Route("/{gridName}/massAction/{actionName}", name="oro_payment_methods_configs_massaction")
     * @Acl(
     *     id="oro_payment_methods_configs_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroPaymentBundle:PaymentMethodsConfigsRule"
     * )
     * @CsrfProtection()
     *
     * @param string $gridName
     * @param string $actionName
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function markMassAction($gridName, $actionName, Request $request)
    {
        $massActionDispatcher = $this->get(MassActionDispatcher::class);

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
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
