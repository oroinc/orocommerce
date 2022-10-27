<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for customer tax codes.
 */
class CustomerTaxCodeController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_customer_tax_code_index")
     * @Template
     * @AclAncestor("oro_tax_customer_tax_code_view")
     */
    public function indexAction(): array
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
     */
    public function viewAction(CustomerTaxCode $customerTaxCode): array
    {
        return [
            'entity' => $customerTaxCode
        ];
    }

    /**
     * @Route("/create", name="oro_tax_customer_tax_code_create")
     * @Template("@OroTax/CustomerTaxCode/update.html.twig")
     * @Acl(
     *      id="oro_tax_customer_tax_code_create",
     *      type="entity",
     *      class="OroTaxBundle:CustomerTaxCode",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
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
     */
    public function updateAction(CustomerTaxCode $customerTaxCode): array|RedirectResponse
    {
        return $this->update($customerTaxCode);
    }

    protected function update(CustomerTaxCode $customerTaxCode): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $customerTaxCode,
            $this->createForm(CustomerTaxCodeType::class, $customerTaxCode),
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.customer_tax_code.saved.message')
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
