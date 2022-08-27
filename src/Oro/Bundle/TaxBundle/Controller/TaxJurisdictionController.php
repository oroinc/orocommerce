<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax jurisdictions.
 */
class TaxJurisdictionController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_jurisdiction_index")
     * @Template
     * @AclAncestor("oro_tax_jurisdiction_view")     *
     * @return
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => TaxJurisdiction::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_jurisdiction_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_jurisdiction_view",
     *      type="entity",
     *      class="OroTaxBundle:TaxJurisdiction",
     *      permission="VIEW"
     * )
     */
    public function viewAction(TaxJurisdiction $taxJurisdiction): array
    {
        return [
            'entity' => $taxJurisdiction
        ];
    }

    /**
     * @Route("/create", name="oro_tax_jurisdiction_create")
     * @Template("@OroTax/TaxJurisdiction/update.html.twig")
     * @Acl(
     *      id="oro_tax_jurisdiction_create",
     *      type="entity",
     *      class="OroTaxBundle:TaxJurisdiction",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new TaxJurisdiction());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_jurisdiction_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_jurisdiction_update",
     *      type="entity",
     *      class="OroTaxBundle:TaxJurisdiction",
     *      permission="EDIT"
     * )
     */
    public function updateAction(TaxJurisdiction $taxJurisdiction): array|RedirectResponse
    {
        return $this->update($taxJurisdiction);
    }

    protected function update(TaxJurisdiction $taxJurisdiction): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $taxJurisdiction,
            $this->createForm(TaxJurisdictionType::class, $taxJurisdiction),
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.tax_jurisdiction.saved.message')
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
