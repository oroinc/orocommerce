<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Form\Type\TaxRuleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax rules.
 */
class TaxRuleController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_rule_index")
     * @Template
     * @AclAncestor("oro_tax_rule_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => TaxRule::class
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
     */
    public function viewAction(TaxRule $taxRule): array
    {
        return [
            'entity' => $taxRule
        ];
    }

    /**
     * @Route("/create", name="oro_tax_rule_create")
     * @Template("@OroTax/TaxRule/update.html.twig")
     * @Acl(
     *      id="oro_tax_rule_create",
     *      type="entity",
     *      class="OroTaxBundle:TaxRule",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
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
     */
    public function updateAction(TaxRule $taxRule): array|RedirectResponse
    {
        return $this->update($taxRule);
    }

    protected function update(TaxRule $taxRule): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $taxRule,
            $this->createForm(TaxRuleType::class, $taxRule),
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.taxrule.saved.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
