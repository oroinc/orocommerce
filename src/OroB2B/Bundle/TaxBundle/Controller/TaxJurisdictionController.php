<?php

namespace OroB2B\Bundle\TaxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;

class TaxJurisdictionController extends Controller
{
    /**
     * @Route("/", name="orob2b_tax_jurisdiction_index")
     * @Template
     * @AclAncestor("orob2b_tax_jurisdiction_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_tax.entity.tax_jurisdiction.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_tax_jurisdiction_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_jurisdiction_view",
     *      type="entity",
     *      class="OroB2BTaxBundle:TaxJurisdiction",
     *      permission="VIEW"
     * )
     *
     * @param TaxJurisdiction $taxJurisdiction
     * @return array
     */
    public function viewAction(TaxJurisdiction $taxJurisdiction)
    {
        return [
            'entity' => $taxJurisdiction
        ];
    }

    /**
     * @Route("/create", name="orob2b_tax_jurisdiction_create")
     * @Template("OroB2BTaxBundle:TaxJurisdiction:update.html.twig")
     * @Acl(
     *      id="orob2b_tax_jurisdiction_create",
     *      type="entity",
     *      class="OroB2BTaxBundle:TaxJurisdiction",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new TaxJurisdiction());
    }

    /**
     * @Route("/update/{id}", name="orob2b_tax_jurisdiction_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_jurisdiction_update",
     *      type="entity",
     *      class="OroB2BTaxBundle:TaxJurisdiction",
     *      permission="EDIT"
     * )
     *
     * @param TaxJurisdiction $taxJurisdiction
     * @return array
     */
    public function updateAction(TaxJurisdiction $taxJurisdiction)
    {
        return $this->update($taxJurisdiction);
    }

    /**
     * @param TaxJurisdiction $taxJurisdiction
     * @return array|RedirectResponse
     */
    protected function update(TaxJurisdiction $taxJurisdiction)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $taxJurisdiction,
            $this->createForm(TaxJurisdictionType::NAME, $taxJurisdiction),
            function (TaxJurisdiction $taxJurisdiction) {
                return [
                    'route' => 'orob2b_tax_jurisdiction_update',
                    'parameters' => ['id' => $taxJurisdiction->getId()]
                ];
            },
            function (TaxJurisdiction $taxJurisdiction) {
                return [
                    'route' => 'orob2b_tax_jurisdiction_view',
                    'parameters' => ['id' => $taxJurisdiction->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.tax.controller.tax_jurisdiction.saved.message')
        );
    }
}
