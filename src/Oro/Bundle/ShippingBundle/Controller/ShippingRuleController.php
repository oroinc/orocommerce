<?php

namespace Oro\Bundle\ShippingBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Form\Handler\ShippingRuleHandler;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShippingRuleController extends Controller
{
    /**
     * @Route("/", name="orob2b_shipping_rule_index")
     * @Template
     * @AclAncestor("orob2b_shipping_rule_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_shipping.entity.shipping_rule.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_shipping_rule_create")
     * @Template("OroShippingBundle:ShippingRule:update.html.twig")
     * @Acl(
     *     id="orob2b_shipping_rule_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroShippingBundle:ShippingRule"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request)
    {
        return $this->update(new ShippingRule(), $request);
    }

    /**
     * @Route("/view/{id}", name="orob2b_shipping_rule_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_shipping_rule_view",
     *      type="entity",
     *      class="OroShippingBundle:ShippingRule",
     *      permission="VIEW"
     * )
     *
     * @param ShippingRule $shippingRule
     *
     * @return array
     */
    public function viewAction(ShippingRule $shippingRule)
    {
        return [
            'entity' => $shippingRule,
        ];
    }

    /**
     * @Route("/update/{id}", name="orob2b_shipping_rule_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="orob2b_shipping_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroShippingBundle:ShippingRule"
     * )
     * @param Request $request
     * @param ShippingRule $entity
     *
     * @return array
     */
    public function updateAction(Request $request, ShippingRule $entity)
    {
        return $this->update($entity, $request);
    }

    /**
     * @param ShippingRule $entity
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function update(ShippingRule $entity, Request $request)
    {
        $form = $this->createForm(ShippingRuleType::class);
        if ($this->get('oro_shipping.form.handler.shipping_rule')->process($form, $entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.shipping.controller.rule.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        $isUpdateOnly = $request->get(ShippingRuleHandler::UPDATE_FLAG, false);

        // take different form due to JS validation should be shown even in case when it was not validated on backend
        if ($isUpdateOnly) {
            $form = $this->createForm(ShippingRuleType::class, $form->getData());
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView()
        ];
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="orob2b_status_shipping_rule_massaction")
     * @Acl(
     *     id="orob2b_shipping_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroShippingBundle:ShippingRule"
     * )
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
