<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleType;

class ShippingRuleController extends Controller
{
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
        $form = $this->createForm(ShippingRuleType::NAME, $entity);
        return $this->get('oro_form.model.update_handler')->update(
            $entity,
            $form,
            $this->get('translator')->trans('orob2b.shipping.controller.rule.saved.message')
        );
    }
}
