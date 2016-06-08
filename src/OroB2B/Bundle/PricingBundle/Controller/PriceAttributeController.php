<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceAttributeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttribute;

class PriceAttributeController extends Controller
{
    /**
     * @Route("/", name="orob2b_pricing_price_attribute_index")
     * @Template
     * @AclAncestor("orob2b_pricing_price_attribute_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container
                ->getParameter('orob2b_pricing.entity.price_attribute.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_pricing_price_attribute_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_attribute_view",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceAttribute",
     *      permission="VIEW"
     * )
     *
     * @param PriceAttribute $priceAttribute
     * @return array
     */
    public function viewAction(PriceAttribute $priceAttribute)
    {
        return [
            'entity' => $priceAttribute,
        ];
    }

    /**
     * @Route("/create", name="orob2b_pricing_price_attribute_create")
     * @Template("OroB2BPricingBundle:PriceAttribute:update.html.twig")
     * @Acl(
     *      id="orob2b_pricing_price_attribute_create",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceAttribute",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new PriceAttribute());
    }

    /**
     * @Route("/update/{id}", name="orob2b_pricing_price_attribute_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_attribute_update",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceAttribute",
     *      permission="EDIT"
     * )
     *
     * @param PriceAttribute $priceAttribute
     * @return array
     */
    public function updateAction(PriceAttribute $priceAttribute)
    {
        return $this->update($priceAttribute);
    }

    /**
     * @param PriceAttribute $priceAttribute
     * @return array|RedirectResponse
     */
    protected function update(PriceAttribute $priceAttribute)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $priceAttribute,
            $this->createForm(PriceAttributeType::NAME, $priceAttribute),
            function (PriceAttribute $priceAttribute) {
                return [
                    'route' => 'orob2b_pricing_price_attribute_update',
                    'parameters' => ['id' => $priceAttribute->getId()],
                ];
            },
            function (PriceAttribute $priceAttribute) {
                return [
                    'route' => 'orob2b_pricing_price_attribute_view',
                    'parameters' => ['id' => $priceAttribute->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.pricing.controller.price_attribute.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_pricing_price_attribute_info", requirements={"id"="\d+"})
     * @Template("OroB2BPricingBundle:PriceAttribute/widget:info.html.twig")
     * @AclAncestor("orob2b_pricing_price_attribute_view")
     *
     * @param PriceAttribute $priceAttribute
     * @return array
     */
    public function infoAction(PriceAttribute $priceAttribute)
    {
        return [
            'entity' => $priceAttribute,
            'treeData' => $this->get('orob2b_pricing_price_attribute.price_attribute_tree_handler')->createTree($priceAttribute),
        ];
    }
}
