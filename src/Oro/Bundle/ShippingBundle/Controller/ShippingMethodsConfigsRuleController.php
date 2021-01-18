<?php

namespace Oro\Bundle\ShippingBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Form\Handler\ShippingMethodsConfigsRuleHandler;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Shipping Methods Configs Rule Controller
 */
class ShippingMethodsConfigsRuleController extends AbstractController
{
    /**
     * @Route("/", name="oro_shipping_methods_configs_rule_index")
     * @Template
     * @AclAncestor("oro_shipping_methods_configs_rule_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => ShippingMethodsConfigsRule::class
        ];
    }

    /**
     * @Route("/create", name="oro_shipping_methods_configs_rule_create")
     * @Template("OroShippingBundle:ShippingMethodsConfigsRule:update.html.twig")
     * @Acl(
     *     id="oro_shipping_methods_configs_rule_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroShippingBundle:ShippingMethodsConfigsRule"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request)
    {
        return $this->update(new ShippingMethodsConfigsRule(), $request);
    }

    /**
     * @Route("/view/{id}", name="oro_shipping_methods_configs_rule_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_shipping_methods_configs_rule_view",
     *      type="entity",
     *      class="OroShippingBundle:ShippingMethodsConfigsRule",
     *      permission="VIEW"
     * )
     *
     * @param ShippingMethodsConfigsRule $shippingRule
     *
     * @return array
     */
    public function viewAction(ShippingMethodsConfigsRule $shippingRule)
    {
        return [
            'entity' => $shippingRule,
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_shipping_methods_configs_rule_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="oro_shipping_methods_configs_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroShippingBundle:ShippingMethodsConfigsRule"
     * )
     * @param Request $request
     * @param ShippingMethodsConfigsRule $entity
     *
     * @return array
     */
    public function updateAction(Request $request, ShippingMethodsConfigsRule $entity)
    {
        return $this->update($entity, $request);
    }

    /**
     * @param ShippingMethodsConfigsRule $entity
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function update(ShippingMethodsConfigsRule $entity, Request $request)
    {
        $form = $this->createForm(ShippingMethodsConfigsRuleType::class);
        if ($this->get('oro_shipping.form.handler.shipping_methods_configs_rule')->process($form, $entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.shipping.controller.rule.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        if ($request->get(ShippingMethodsConfigsRuleHandler::UPDATE_FLAG, false)) {
            // take different form due to JS validation should be shown even in case
            // when it was not validated on backend
            $form = $this->createForm(ShippingMethodsConfigsRuleType::class, $form->getData());
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView()
        ];
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_status_shipping_rule_massaction")
     * @Acl(
     *     id="oro_shipping_methods_configs_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroShippingBundle:ShippingMethodsConfigsRule"
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
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }
}
