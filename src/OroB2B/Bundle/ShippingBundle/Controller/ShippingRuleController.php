<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     * @Route("/update/{id}", name="orob2b_shipping_rule_update", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_shipping_rule_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroB2BShippingBundle:ShippingRule"
     * )
     * @Template()
     * @param ShippingRule $shippingRule
     * @return array
     */
    public function updateAction(ShippingRule $shippingRule)
    {
        return $this->update($shippingRule);
    }

    /**
     * @param ShippingRule $shippingRule
     *
     * @return array
     */
    protected function update(ShippingRule $shippingRule)
    {
        return [
        ];
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="orob2b_status_shipping_rule_massaction")
     *
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function markMassAction($gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $this->getRequest());

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }
}
