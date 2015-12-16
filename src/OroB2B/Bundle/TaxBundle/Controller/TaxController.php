<?php

namespace OroB2B\Bundle\TaxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Form\Type\TaxType;

class TaxController extends Controller
{
    /**
     * @Route("/", name="orob2b_tax_index")
     * @Template
     * @AclAncestor("orob2b_tax_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_tax.entity.tax.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_tax_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_view",
     *      type="entity",
     *      class="OroB2BTaxBundle:Tax",
     *      permission="VIEW"
     * )
     *
     * @param Tax $tax
     * @return array
     */
    public function viewAction(Tax $tax)
    {
        return [
            'entity' => $tax
        ];
    }

    /**
     * @Route("/create", name="orob2b_tax_create")
     * @Template("OroB2BTaxBundle:Tax:update.html.twig")
     * @Acl(
     *      id="orob2b_tax_create",
     *      type="entity",
     *      class="OroB2BTaxBundle:Tax",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new Tax());
    }

    /**
     * @Route("/update/{id}", name="orob2b_tax_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_update",
     *      type="entity",
     *      class="OroB2BTaxBundle:Tax",
     *      permission="EDIT"
     * )
     *
     * @param Tax $tax
     * @return array
     */
    public function updateAction(Tax $tax)
    {
        return $this->update($tax);
    }

    /**
     * @param Tax $tax
     * @return array|RedirectResponse
     */
    protected function update(Tax $tax)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $tax,
            $this->createForm(TaxType::NAME, $tax),
            function (Tax $tax) {
                return [
                    'route' => 'orob2b_tax_update',
                    'parameters' => ['id' => $tax->getId()]
                ];
            },
            function (Tax $tax) {
                return [
                    'route' => 'orob2b_tax_view',
                    'parameters' => ['id' => $tax->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.tax.controller.tax.saved.message')
        );
    }
}
