<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use Oro\Bundle\PricingBundle\Handler\ProductPriceHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds actions to update, delete and get prices by customer or matching prices via AJAX
 */
class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-customer", name="oro_pricing_price_by_customer", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductPricesByCustomerAction(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    /**
     * Edit product form
     *
     * @Route(
     *     "/update/{priceList}/{id}",
     *     name="oro_product_price_update_widget",
     *     requirements={"priceListId"="\d+"}
     * )
     * @Template("OroPricingBundle:ProductPrice:widget/update.html.twig")
     * @Acl(
     *      id="oro_pricing_product_price_update",
     *      type="entity",
     *      class="OroPricingBundle:ProductPrice",
     *      permission="EDIT"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $priceList = $this->getDoctrine()->getRepository(PriceList::class)->find($request->get('priceList'));
        $prices = $this->getDoctrine()->getRepository(ProductPrice::class)
            ->findByPriceList(
                $this->get(ShardManager::class),
                $priceList,
                ['id' => $request->get('id')]
            );

        $productPrice = $prices[0];

        $handler = $this->get(UpdateHandlerFacade::class);
        $priceHandler = $this->get(ProductPriceHandler::class);
        return $handler->update(
            $productPrice,
            PriceListProductPriceType::class,
            null,
            $request,
            $priceHandler,
            null
        );
    }

    /**
     * @Route("/get-matching-price", name="oro_pricing_matching_price", methods={"GET"})
     * @AclAncestor("oro_pricing_product_price_view")
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get(MatchingPriceProvider::class)->getMatchingPrices(
            $lineItems,
            $this->get(ProductPriceScopeCriteriaRequestHandler::class)->getPriceScopeCriteria()
        );

        return new JsonResponse($matchedPrices);
    }

    /**
     * @Route(
     *     "/delete-product-price/{priceListId}/{productPriceId}",
     *      name="oro_product_price_delete",
     *      methods={"DELETE"}
     * )
     * @ParamConverter("priceList", class="OroPricingBundle:PriceList", options={"id" = "priceListId"})
     * @Acl(
     *      id="oro_pricing_product_price_delete",
     *      type="entity",
     *      class="OroPricingBundle:ProductPrice",
     *      permission="DELETE"
     * )
     * @CsrfProtection()
     *
     * {@inheritdoc}
     */
    public function deleteAction(PriceList $priceList, $productPriceId)
    {
        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->getDoctrine()->getRepository(ProductPrice::class);
        /** @var ProductPrice $productPrice */
        $productPrice = $priceRepository
            ->findByPriceList(
                $this->get(ShardManager::class),
                $priceList,
                ['id' => $productPriceId]
            );
        $code = JsonResponse::HTTP_OK;
        $errors = new ArrayCollection();
        $message = '';

        if (empty($productPrice)) {
            $code = JsonResponse::HTTP_NOT_FOUND;
        } else {
            try {
                $priceManager = $this->get(PriceManager::class);
                $priceManager->remove($productPrice[0]);
                $priceManager->flush();
            } catch (\Exception $e) {
                $code = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                $message = $e->getMessage();
            }
        }

        $response = [
            'successful' => $code === JsonResponse::HTTP_OK,
            'message' => $message,
            'messages' => $this->prepareMessages($errors),
            'refreshGrid' => $this->get(ContextHelper::class)->getActionData()->getRefreshGrid(),
            'flashMessages' => $this->get('session')->getFlashBag()->all()
        ];

        return new JsonResponse($response, $code);
    }

    /**
     * @param Collection $messages
     * @return array
     */
    protected function prepareMessages(Collection $messages)
    {
        $result = [];

        foreach ($messages as $message) {
            $result[] = $this->get(TranslatorInterface::class)->trans($message['message'], $message['parameters']);
        }

        return $result;
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
                ShardManager::class,
                UpdateHandlerFacade::class,
                ProductPriceHandler::class,
                MatchingPriceProvider::class,
                ProductPriceScopeCriteriaRequestHandler::class,
                PriceManager::class,
                ContextHelper::class,
            ]
        );
    }
}
