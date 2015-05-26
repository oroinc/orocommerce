<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_pricing_price_list_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_pricing_price_list_view",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceList",
     *      permission="VIEW"
     * )
     * @param PriceList $priceList
     * @return array
     */
    public function viewAction(PriceList $priceList)
    {
        return [
            'entity' => $priceList
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_pricing_price_list_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_pricing_price_list_view")
     * @param PriceList $priceList
     * @return array
     */
    public function infoAction(PriceList $priceList)
    {
        return [
            'priceList' => $priceList
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
     * @Template("OroB2BPricingBundle:PriceList:update.html.twig")
     * @Acl(
     *      id="orob2b_pricing_price_list_create",
     *      type="entity",
     *      class="OroB2BPricingBundle:PriceList",
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
     *      class="OroB2BPricingBundle:PriceList",
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
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $priceList,
            $this->get('orob2b_pricing.form.price_list'),
            function (PriceList $priceList) {
                return array(
                    'route' => 'orob2b_pricing_price_list_update',
                    'parameters' => array('id' => $priceList->getId())
                );
            },
            function (PriceList $priceList) {
                return array(
                    'route' => 'orob2b_pricing_price_list_view',
                    'parameters' => array('id' => $priceList->getId())
                );
            },
            $this->get('translator')->trans('orob2b.pricing.controller.price_list.saved.message'),
            $this->get('orob2b_pricing.form.handler.price_list')
        );
    }

    /**
     * @Route("/default/{id}", name="orob2b_pricing_price_list_default", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_pricing_price_list_update")
     *
     * @param PriceList $priceList
     * @return JsonResponse
     */
    public function defaultAction(PriceList $priceList)
    {
        $successful = true;

        try {
            $this->get('orob2b_pricing.model.price_list_state_manager')->applyDefault($priceList);
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Set default price list failed: %s: %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );

            $successful = false;
        }

        return new JsonResponse(['successful' => $successful]);
    }
}
