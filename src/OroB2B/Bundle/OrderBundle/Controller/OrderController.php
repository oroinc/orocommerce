<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\OrderRequestHandler;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;

class OrderController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_order_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="VIEW"
     * )
     *
     * @param Order $order
     *
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'entity' => $order,
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_order_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_order_view")
     *
     * @param Order $order
     *
     * @return array
     */
    public function infoAction(Order $order)
    {
        return [
            'order' => $order,
        ];
    }

    /**
     * @Route("/", name="orob2b_order_index")
     * @Template
     * @AclAncestor("orob2b_order_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_order.entity.order.class'),
        ];
    }

    /**
     * Create order form
     *
     * @Route("/create", name="orob2b_order_create")
     * @Template("OroB2BOrderBundle:Order:update.html.twig")
     * @Acl(
     *      id="orob2b_order_create",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        $order = new Order();
        /** @var LocaleSettings $localeSettings */
        $localeSettings = $this->get('oro_locale.settings');
        $order->setCurrency($localeSettings->getCurrency());

        return $this->update($order);
    }

    /**
     * Edit order form
     *
     * @Route("/update/{id}", name="orob2b_order_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_update",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Order $order)
    {
        return $this->update($order);
    }

    /**
     * @param Order $order
     *
     * @return array|RedirectResponse
     */
    protected function update(Order $order)
    {
        if (in_array($this->get('request')->getMethod(), ['POST', 'PUT'], true)) {
            $order->setAccount($this->getOrderHandler()->getAccount());
            $order->setAccountUser($this->getOrderHandler()->getAccountUser());
        }

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $order,
            $this->createForm(OrderType::NAME, $order),
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_update',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_view',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.order.controller.order.saved.message'),
            null,
            function (Order $order, FormInterface $form, Request $request) {
                return [
                    'form' => $form->createView(),
                    'entity' => $order,
                    'isWidgetContext' => (bool)$request->get('_wid', false),
                    'isShippingAddressGranted' => $this->getOrderAddressSecurityProvider()
                        ->isAddressGranted($order, AddressType::TYPE_SHIPPING),
                    'isBillingAddressGranted' => $this->getOrderAddressSecurityProvider()
                        ->isAddressGranted($order, AddressType::TYPE_BILLING),
                    'tierPrices' => $this->getTierPrices($order),
                    'matchedPrices' => $this->getMatchedPrices($order),
                ];
            }
        );
    }

    /**
     * @return OrderAddressSecurityProvider
     */
    protected function getOrderAddressSecurityProvider()
    {
        return $this->get('orob2b_order.order.provider.order_address_security');
    }

    /**
     * @return OrderRequestHandler
     */
    protected function getOrderHandler()
    {
        return $this->get('orob2b_order.model.order_request_handler');
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getTierPrices(Order $order)
    {
        $tierPrices = [];

        $priceList = $order->getPriceList();
        if (!$priceList) {
            $priceList = $this->get('orob2b_pricing.model.frontend.price_list_request_handler')->getPriceList();
        }

        $productIds = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() !== null;
            }
        )->map(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct()->getId();
            }
        );

        if ($productIds) {
            $tierPrices = $this->get('orob2b_pricing.provider.product_price')->getPriceByPriceListIdAndProductIds(
                $priceList->getId(),
                $productIds->toArray(),
                $order->getCurrency()
            );
        }

        return $tierPrices;
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getMatchedPrices(Order $order)
    {
        $matchedPrices = [];

        $productUnitQuantities = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() && $lineItem->getProductUnit() && $lineItem->getQuantity();
            }
        )->map(
            function (OrderLineItem $lineItem) {
                return new ProductUnitQuantity(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity()
                );
            }
        );

        if ($productUnitQuantities) {
            $matchedPrices = $this->get('orob2b_pricing.provider.product_price')->getMatchedPrices(
                $productUnitQuantities->toArray(),
                $order->getCurrency(),
                $order->getPriceList()
            );
        }

        /** @var Price $price */
        foreach ($matchedPrices as &$price) {
            if ($price) {
                $price = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency()
                ];
            }
        }

        return $matchedPrices;
    }
}
