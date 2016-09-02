<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;

class PriceListController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_pricing_price_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_list_view",
     *      type="entity",
     *      class="OroPricingBundle:PriceList",
     *      permission="VIEW"
     * )
     * @param PriceList $priceList
     * @return array
     */
    public function viewAction(PriceList $priceList)
    {
        if (!$priceList->isActual()) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('oro.pricing.pricelist.not_actual.recalculation')
            );
        }

        return [
            'entity' => $priceList,
            'product_price_entity_class' => $this->container->getParameter('orob2b_pricing.entity.product_price.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_pricing_price_list_info", requirements={"id"="\d+"})
     * @Template("OroPricingBundle:PriceList/widget:info.html.twig")
     * @AclAncestor("orob2b_pricing_price_list_view")
     * @param PriceList $priceList
     * @return array
     */
    public function infoAction(PriceList $priceList)
    {
        return [
            'entity' => $priceList
        ];
    }

    /**
     * @Route("/", name="orob2b_pricing_price_list_index")
     * @Template
     * @AclAncestor("orob2b_pricing_price_list_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_pricing.entity.price_list.class')
        ];
    }

    /**
     * Create price_list form
     *
     * @Route("/create", name="orob2b_pricing_price_list_create")
     * @Template("OroPricingBundle:PriceList:update.html.twig")
     * @Acl(
     *      id="orob2b_pricing_price_list_create",
     *      type="entity",
     *      class="OroPricingBundle:PriceList",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new PriceList());
    }

    /**
     * Edit price_list form
     *
     * @Route("/update/{id}", name="orob2b_pricing_price_list_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_list_update",
     *      type="entity",
     *      class="OroPricingBundle:PriceList",
     *      permission="EDIT"
     * )
     * @param PriceList $priceList
     * @return array|RedirectResponse
     */
    public function updateAction(PriceList $priceList)
    {
        return $this->update($priceList);
    }

    /**
     * @param PriceList $priceList
     * @return array|RedirectResponse
     */
    protected function update(PriceList $priceList)
    {
        $form = $this->createForm(PriceListType::NAME, $priceList);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $priceList,
            $form,
            function (PriceList $priceList) {
                return [
                    'route' => 'orob2b_pricing_price_list_update',
                    'parameters' => ['id' => $priceList->getId()]
                ];
            },
            function (PriceList $priceList) {
                return [
                    'route' => 'orob2b_pricing_price_list_view',
                    'parameters' => ['id' => $priceList->getId()]
                ];
            },
            $this->get('translator')->trans('oro.pricing.controller.price_list.saved.message')
        );
    }
}
