<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for customer tax codes.
 */
class CustomerTaxCodeController extends AbstractController
{
    #[Route(path: '/', name: 'oro_tax_customer_tax_code_index')]
    #[Template('@OroTax/CustomerTaxCode/index.html.twig')]
    #[AclAncestor('oro_tax_customer_tax_code_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => CustomerTaxCode::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_tax_customer_tax_code_view', requirements: ['id' => '\d+'])]
    #[Template('@OroTax/CustomerTaxCode/view.html.twig')]
    #[Acl(id: 'oro_tax_customer_tax_code_view', type: 'entity', class: CustomerTaxCode::class, permission: 'VIEW')]
    public function viewAction(CustomerTaxCode $customerTaxCode): array
    {
        return [
            'entity' => $customerTaxCode
        ];
    }

    #[Route(path: '/create', name: 'oro_tax_customer_tax_code_create')]
    #[Template('@OroTax/CustomerTaxCode/update.html.twig')]
    #[Acl(id: 'oro_tax_customer_tax_code_create', type: 'entity', class: CustomerTaxCode::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new CustomerTaxCode());
    }

    #[Route(path: '/update/{id}', name: 'oro_tax_customer_tax_code_update', requirements: ['id' => '\d+'])]
    #[Template('@OroTax/CustomerTaxCode/update.html.twig')]
    #[Acl(id: 'oro_tax_customer_tax_code_update', type: 'entity', class: CustomerTaxCode::class, permission: 'EDIT')]
    public function updateAction(CustomerTaxCode $customerTaxCode): array|RedirectResponse
    {
        return $this->update($customerTaxCode);
    }

    protected function update(CustomerTaxCode $customerTaxCode): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $customerTaxCode,
            $this->createForm(CustomerTaxCodeType::class, $customerTaxCode),
            $this->container->get(TranslatorInterface::class)
                ->trans('oro.tax.controller.customer_tax_code.saved.message')
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
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
