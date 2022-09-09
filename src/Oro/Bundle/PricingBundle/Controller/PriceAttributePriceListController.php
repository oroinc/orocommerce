<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceAttributePriceListType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for price attributes.
 */
class PriceAttributePriceListController extends AbstractController
{
    /**
     * @Route("/", name="oro_pricing_price_attribute_price_list_index")
     * @Template
     * @AclAncestor("oro_pricing_price_attribute_price_list_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => PriceAttributePriceList::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_pricing_price_attribute_price_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_pricing_price_attribute_price_list_view",
     *      type="entity",
     *      class="OroPricingBundle:PriceAttributePriceList",
     *      permission="VIEW"
     * )
     */
    public function viewAction(PriceAttributePriceList $priceAttribute): array
    {
        return [
            'entity' => $priceAttribute,
        ];
    }

    /**
     * @Route("/create", name="oro_pricing_price_attribute_price_list_create")
     * @Template("@OroPricing/PriceAttributePriceList/update.html.twig")
     * @Acl(
     *      id="oro_pricing_price_attribute_price_list_create",
     *      type="entity",
     *      class="OroPricingBundle:PriceAttributePriceList",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new PriceAttributePriceList());
    }

    /**
     * @Route("/update/{id}", name="oro_pricing_price_attribute_price_list_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_pricing_price_attribute_price_list_update",
     *      type="entity",
     *      class="OroPricingBundle:PriceAttributePriceList",
     *      permission="EDIT"
     * )
     */
    public function updateAction(PriceAttributePriceList $priceAttribute): array|RedirectResponse
    {
        return $this->update($priceAttribute);
    }

    protected function update(PriceAttributePriceList $priceAttribute): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $priceAttribute,
            $this->createForm(PriceAttributePriceListType::class, $priceAttribute),
            $this->get(TranslatorInterface::class)->trans(
                'oro.pricing.controller.price_attribute_price_list.saved.message'
            )
        );
    }

    /**
     * @Route("/info/{id}", name="oro_pricing_price_attribute_price_list_info", requirements={"id"="\d+"})
     * @Template("@OroPricing/PriceAttributePriceList/widget/info.html.twig")
     * @AclAncestor("oro_pricing_price_attribute_price_list_view")
     */
    public function infoAction(PriceAttributePriceList $priceAttribute): array
    {
        return [
            'entity' => $priceAttribute
        ];
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
