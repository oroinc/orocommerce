<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax jurisdictions.
 */
class TaxJurisdictionController extends AbstractController
{
    #[Route(path: '/', name: 'oro_tax_jurisdiction_index')]
    #[Template('@OroTax/TaxJurisdiction/index.html.twig')]
    #[AclAncestor('oro_tax_jurisdiction_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => TaxJurisdiction::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_tax_jurisdiction_view', requirements: ['id' => '\d+'])]
    #[Template('@OroTax/TaxJurisdiction/view.html.twig')]
    #[Acl(id: 'oro_tax_jurisdiction_view', type: 'entity', class: TaxJurisdiction::class, permission: 'VIEW')]
    public function viewAction(TaxJurisdiction $taxJurisdiction): array
    {
        return [
            'entity' => $taxJurisdiction
        ];
    }

    #[Route(path: '/create', name: 'oro_tax_jurisdiction_create')]
    #[Template('@OroTax/TaxJurisdiction/update.html.twig')]
    #[Acl(id: 'oro_tax_jurisdiction_create', type: 'entity', class: TaxJurisdiction::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new TaxJurisdiction());
    }

    #[Route(path: '/update/{id}', name: 'oro_tax_jurisdiction_update', requirements: ['id' => '\d+'])]
    #[Template('@OroTax/TaxJurisdiction/update.html.twig')]
    #[Acl(id: 'oro_tax_jurisdiction_update', type: 'entity', class: TaxJurisdiction::class, permission: 'EDIT')]
    public function updateAction(TaxJurisdiction $taxJurisdiction): array|RedirectResponse
    {
        return $this->update($taxJurisdiction);
    }

    protected function update(TaxJurisdiction $taxJurisdiction): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $taxJurisdiction,
            $this->createForm(TaxJurisdictionType::class, $taxJurisdiction),
            $this->container->get(TranslatorInterface::class)
                ->trans('oro.tax.controller.tax_jurisdiction.saved.message')
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
