<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceAttributePriceListType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for price attributes.
 */
class PriceAttributePriceListController extends AbstractController
{
    #[Route(path: '/', name: 'oro_pricing_price_attribute_price_list_index')]
    #[Template]
    #[AclAncestor('oro_pricing_price_attribute_price_list_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => PriceAttributePriceList::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_pricing_price_attribute_price_list_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(
        id: 'oro_pricing_price_attribute_price_list_view',
        type: 'entity',
        class: PriceAttributePriceList::class,
        permission: 'VIEW'
    )]
    public function viewAction(PriceAttributePriceList $priceAttribute): array
    {
        return [
            'entity' => $priceAttribute,
        ];
    }

    #[Route(path: '/create', name: 'oro_pricing_price_attribute_price_list_create')]
    #[Template('@OroPricing/PriceAttributePriceList/update.html.twig')]
    #[Acl(
        id: 'oro_pricing_price_attribute_price_list_create',
        type: 'entity',
        class: PriceAttributePriceList::class,
        permission: 'CREATE'
    )]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new PriceAttributePriceList());
    }

    #[Route(
        path: '/update/{id}',
        name: 'oro_pricing_price_attribute_price_list_update',
        requirements: ['id' => '\d+']
    )]
    #[Template]
    #[Acl(
        id: 'oro_pricing_price_attribute_price_list_update',
        type: 'entity',
        class: PriceAttributePriceList::class,
        permission: 'EDIT'
    )]
    public function updateAction(PriceAttributePriceList $priceAttribute): array|RedirectResponse
    {
        return $this->update($priceAttribute);
    }

    protected function update(PriceAttributePriceList $priceAttribute): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $priceAttribute,
            $this->createForm(PriceAttributePriceListType::class, $priceAttribute),
            $this->container->get(TranslatorInterface::class)->trans(
                'oro.pricing.controller.price_attribute_price_list.saved.message'
            )
        );
    }

    #[Route(path: '/info/{id}', name: 'oro_pricing_price_attribute_price_list_info', requirements: ['id' => '\d+'])]
    #[Template('@OroPricing/PriceAttributePriceList/widget/info.html.twig')]
    #[AclAncestor('oro_pricing_price_attribute_price_list_view')]
    public function infoAction(PriceAttributePriceList $priceAttribute): array
    {
        return [
            'entity' => $priceAttribute
        ];
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
