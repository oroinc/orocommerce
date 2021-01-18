<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceAttributePriceListType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD for price attributes.
 */
class PriceAttributePriceListController extends AbstractController
{
    /**
     * @Route("/", name="oro_pricing_price_attribute_price_list_index")
     * @Template
     * @AclAncestor("oro_pricing_price_attribute_price_list_view")
     *
     * @return array
     */
    public function indexAction()
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
     *
     * @param PriceAttributePriceList $priceAttribute
     * @return array
     */
    public function viewAction(PriceAttributePriceList $priceAttribute)
    {
        return [
            'entity' => $priceAttribute,
        ];
    }

    /**
     * @Route("/create", name="oro_pricing_price_attribute_price_list_create")
     * @Template("OroPricingBundle:PriceAttributePriceList:update.html.twig")
     * @Acl(
     *      id="oro_pricing_price_attribute_price_list_create",
     *      type="entity",
     *      class="OroPricingBundle:PriceAttributePriceList",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
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
     *
     * @param PriceAttributePriceList $priceAttribute
     * @return array
     */
    public function updateAction(PriceAttributePriceList $priceAttribute)
    {
        return $this->update($priceAttribute);
    }

    /**
     * @param PriceAttributePriceList $priceAttribute
     * @return array|RedirectResponse
     */
    protected function update(PriceAttributePriceList $priceAttribute)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $priceAttribute,
            $this->createForm(PriceAttributePriceListType::class, $priceAttribute),
            function (PriceAttributePriceList $priceAttribute) {
                return [
                    'route' => 'oro_pricing_price_attribute_price_list_update',
                    'parameters' => ['id' => $priceAttribute->getId()],
                ];
            },
            function (PriceAttributePriceList $priceAttribute) {
                return [
                    'route' => 'oro_pricing_price_attribute_price_list_view',
                    'parameters' => ['id' => $priceAttribute->getId()],
                ];
            },
            $this->get('translator')->trans('oro.pricing.controller.price_attribute_price_list.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="oro_pricing_price_attribute_price_list_info", requirements={"id"="\d+"})
     * @Template("OroPricingBundle:PriceAttributePriceList/widget:info.html.twig")
     * @AclAncestor("oro_pricing_price_attribute_price_list_view")
     * @param PriceAttributePriceList $priceAttribute
     * @return array
     */
    public function infoAction(PriceAttributePriceList $priceAttribute)
    {
        return [
            'entity' => $priceAttribute
        ];
    }
}
