<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD for customer tax codes.
 */
class CustomerTaxCodeController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_customer_tax_code_index")
     * @Template
     * @AclAncestor("oro_tax_customer_tax_code_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => CustomerTaxCode::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_customer_tax_code_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_customer_tax_code_view",
     *      type="entity",
     *      class="OroTaxBundle:CustomerTaxCode",
     *      permission="VIEW"
     * )
     *
     * @param CustomerTaxCode $customerTaxCode
     * @return array
     */
    public function viewAction(CustomerTaxCode $customerTaxCode)
    {
        return [
            'entity' => $customerTaxCode
        ];
    }

    /**
     * @Route("/create", name="oro_tax_customer_tax_code_create")
     * @Template("OroTaxBundle:CustomerTaxCode:update.html.twig")
     * @Acl(
     *      id="oro_tax_customer_tax_code_create",
     *      type="entity",
     *      class="OroTaxBundle:CustomerTaxCode",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new CustomerTaxCode());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_customer_tax_code_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_customer_tax_code_update",
     *      type="entity",
     *      class="OroTaxBundle:CustomerTaxCode",
     *      permission="EDIT"
     * )
     *
     * @param CustomerTaxCode $customerTaxCode
     * @return array
     */
    public function updateAction(CustomerTaxCode $customerTaxCode)
    {
        return $this->update($customerTaxCode);
    }

    /**
     * @param CustomerTaxCode $customerTaxCode
     * @return array|RedirectResponse
     */
    protected function update(CustomerTaxCode $customerTaxCode)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $customerTaxCode,
            $this->createForm(CustomerTaxCodeType::class, $customerTaxCode),
            function (CustomerTaxCode $customerTaxCode) {
                return [
                    'route' => 'oro_tax_customer_tax_code_update',
                    'parameters' => ['id' => $customerTaxCode->getId()]
                ];
            },
            function (CustomerTaxCode $customerTaxCode) {
                return [
                    'route' => 'oro_tax_customer_tax_code_view',
                    'parameters' => ['id' => $customerTaxCode->getId()]
                ];
            },
            $this->get('translator')->trans('oro.tax.controller.customer_tax_code.saved.message')
        );
    }
}
