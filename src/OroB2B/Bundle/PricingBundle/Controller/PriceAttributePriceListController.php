<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceAttributePriceListType;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;

class PriceAttributePriceListController extends Controller
{
    /**
     * @Route("/", name="orob2b_pricing_price_attribute_price_list_index")
     * @Template
     * @AclAncestor("orob2b_pricing_price_attribute_price_list_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_pricing.entity.price_attribute_price_list.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_pricing_price_attribute_price_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_attribute_price_list_view",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceAttributePriceList",
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
     * @Route("/create", name="orob2b_pricing_price_attribute_price_list_create")
     * @Template("OroB2BPricingBundle:PriceAttributePriceList:update.html.twig")
     * @Acl(
     *      id="orob2b_pricing_price_attribute_price_list_create",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceAttributePriceList",
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
     * @Route("/update/{id}", name="orob2b_pricing_price_attribute_price_list_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_attribute_price_list_update",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceAttributePriceList",
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
            $this->createForm(PriceAttributePriceListType::NAME, $priceAttribute),
            function (PriceAttributePriceList $priceAttribute) {
                return [
                    'route' => 'orob2b_pricing_price_attribute_price_list_update',
                    'parameters' => ['id' => $priceAttribute->getId()],
                ];
            },
            function (PriceAttributePriceList $priceAttribute) {
                return [
                    'route' => 'orob2b_pricing_price_attribute_price_list_view',
                    'parameters' => ['id' => $priceAttribute->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.pricing.controller.price_attribute_price_list.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_pricing_price_attribute_price_list_info", requirements={"id"="\d+"})
     * @Template("OroB2BPricingBundle:PriceAttributePriceList/widget:info.html.twig")
     * @AclAncestor("orob2b_pricing_price_attribute_price_list_view")
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
