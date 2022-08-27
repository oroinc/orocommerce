<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds actions to update, delete and get price list
 */
class PriceListController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_pricing_price_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_pricing_price_list_view",
     *      type="entity",
     *      class="OroPricingBundle:PriceList",
     *      permission="VIEW"
     * )
     */
    public function viewAction(PriceList $priceList): array
    {
        return [
            'entity' => $priceList,
            'product_price_entity_class' => ProductPrice::class
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_pricing_price_list_info", requirements={"id"="\d+"})
     * @Template("@OroPricing/PriceList/widget/info.html.twig")
     * @AclAncestor("oro_pricing_price_list_view")
     */
    public function infoAction(PriceList $priceList): array
    {
        return [
            'entity' => $priceList
        ];
    }

    /**
     * @Route("/", name="oro_pricing_price_list_index")
     * @Template
     * @AclAncestor("oro_pricing_price_list_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => PriceList::class
        ];
    }

    /**
     * Create price_list form
     *
     * @Route("/create", name="oro_pricing_price_list_create")
     * @Template("@OroPricing/PriceList/update.html.twig")
     * @Acl(
     *      id="oro_pricing_price_list_create",
     *      type="entity",
     *      class="OroPricingBundle:PriceList",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new PriceList());
    }

    /**
     * Edit price_list form
     *
     * @Route("/update/{id}", name="oro_pricing_price_list_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_pricing_price_list_update",
     *      type="entity",
     *      class="OroPricingBundle:PriceList",
     *      permission="EDIT"
     * )
     */
    public function updateAction(PriceList $priceList): array|RedirectResponse
    {
        return $this->update($priceList);
    }

    protected function update(PriceList $priceList): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $priceList,
            $this->createForm(PriceListType::class, $priceList),
            $this->get(TranslatorInterface::class)->trans('oro.pricing.controller.price_list.saved.message')
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
