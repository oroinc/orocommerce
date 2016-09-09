<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Form\Type\TaxRuleType;

class TaxRuleController extends Controller
{
    /**
     * @Route("/", name="oro_tax_rule_index")
     * @Template
     * @AclAncestor("oro_tax_rule_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_tax.entity.tax_rule.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_rule_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_rule_view",
     *      type="entity",
     *      class="OroTaxBundle:TaxRule",
     *      permission="VIEW"
     * )
     *
     * @param TaxRule $taxRule
     * @return array
     */
    public function viewAction(TaxRule $taxRule)
    {
        return [
            'entity' => $taxRule
        ];
    }

    /**
     * @Route("/create", name="oro_tax_rule_create")
     * @Template("OroTaxBundle:TaxRule:update.html.twig")
     * @Acl(
     *      id="oro_tax_rule_create",
     *      type="entity",
     *      class="OroTaxBundle:TaxRule",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new TaxRule());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_rule_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_rule_update",
     *      type="entity",
     *      class="OroTaxBundle:TaxRule",
     *      permission="EDIT"
     * )
     *
     * @param TaxRule $taxRule
     * @return array
     */
    public function updateAction(TaxRule $taxRule)
    {
        return $this->update($taxRule);
    }

    /**
     * @param TaxRule $taxRule
     * @return array|RedirectResponse
     */
    protected function update(TaxRule $taxRule)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $taxRule,
            $this->createForm(TaxRuleType::NAME, $taxRule),
            function (TaxRule $taxRule) {
                return [
                    'route' => 'oro_tax_rule_update',
                    'parameters' => ['id' => $taxRule->getId()]
                ];
            },
            function (TaxRule $taxRule) {
                return [
                    'route' => 'oro_tax_rule_view',
                    'parameters' => ['id' => $taxRule->getId()]
                ];
            },
            $this->get('translator')->trans('oro.tax.controller.taxrule.saved.message')
        );
    }
}
