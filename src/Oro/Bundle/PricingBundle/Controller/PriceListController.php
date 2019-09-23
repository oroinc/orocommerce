<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\PricingBundle\Async\NotificationMessages;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Bundle\PricingBundle\NotificationMessage\Renderer\RendererInterface;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
     * @param PriceList $priceList
     * @return array
     */
    public function viewAction(PriceList $priceList)
    {
        if (!$priceList->isActual()) {
            $this->get(SessionInterface::class)->getFlashBag()->add(
                'warning',
                $this->get(TranslatorInterface::class)->trans('oro.pricing.pricelist.not_actual.recalculation')
            );
        }
        $this->renderNotificationMessages(NotificationMessages::CHANNEL_PRICE_LIST, $priceList);

        return [
            'entity' => $priceList,
            'product_price_entity_class' => ProductPrice::class
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_pricing_price_list_info", requirements={"id"="\d+"})
     * @Template("OroPricingBundle:PriceList/widget:info.html.twig")
     * @AclAncestor("oro_pricing_price_list_view")
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
     * @Route("/", name="oro_pricing_price_list_index")
     * @Template
     * @AclAncestor("oro_pricing_price_list_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => PriceList::class
        ];
    }

    /**
     * Create price_list form
     *
     * @Route("/create", name="oro_pricing_price_list_create")
     * @Template("OroPricingBundle:PriceList:update.html.twig")
     * @Acl(
     *      id="oro_pricing_price_list_create",
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
     * @Route("/update/{id}", name="oro_pricing_price_list_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_pricing_price_list_update",
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
        return $this->get(UpdateHandler::class)->handleUpdate(
            $priceList,
            $this->createForm(PriceListType::class, $priceList),
            function (PriceList $priceList) {
                return [
                    'route' => 'oro_pricing_price_list_update',
                    'parameters' => ['id' => $priceList->getId()]
                ];
            },
            function (PriceList $priceList) {
                return [
                    'route' => 'oro_pricing_price_list_view',
                    'parameters' => ['id' => $priceList->getId()]
                ];
            },
            $this->get(TranslatorInterface::class)->trans('oro.pricing.controller.price_list.saved.message')
        );
    }

    /**
     * @param string|array $channel
     * @param PriceList $priceList
     */
    protected function renderNotificationMessages($channel, PriceList $priceList)
    {
        $messages = $this
            ->get(Messenger::class)
            ->receive($channel, PriceList::class, $priceList->getId());

        $messageRenderer = $this->get(RendererInterface::class);
        foreach ($messages as $message) {
            $messageRenderer->render($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                SessionInterface::class,
                UpdateHandler::class,
                Messenger::class,
                RendererInterface::class,
            ]
        );
    }
}
