<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleType;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

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
     * @Template("OroB2BShippingBundle:ShippingRule:update.html.twig")
     * @Acl(
     *     id="orob2b_shipping_rule_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroB2BShippingBundle:ShippingRule"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new ShippingRule());
    }

    /**
     * @Route("/view/{id}", name="orob2b_shipping_rule_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_shipping_rule_view",
     *      type="entity",
     *      class="OroB2BShippingBundle:ShippingRule",
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
     * @param ShippingRule $entity
     *
     * @Route("/update/{id}", name="orob2b_shipping_rule_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="orob2b_shipping_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BShippingBundle:ShippingRule"
     * )
     * @return array
     */
    public function updateAction(ShippingRule $entity)
    {
        return $this->update($entity);
    }

    /**
     * @param ShippingRule $entity
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function update(ShippingRule $entity)
    {
        $form = $this->createForm(ShippingRuleType::class, $entity);
        return $this->get('oro_form.model.update_handler')->update(
            $entity,
            $form,
            $this->get('translator')->trans('orob2b.shipping.controller.rule.saved.message')
        );
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="orob2b_status_shipping_rule_massaction")
     * @Acl(
     *     id="orob2b_shipping_rule_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BShippingBundle:ShippingRule"
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
