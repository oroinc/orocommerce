<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-customer", name="oro_pricing_price_by_customer")
     * @Method({"GET"})
     *
     * {@inheritdoc}
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
                $this->get('oro_pricing.shard_manager'),
                $priceList,
                ['id' => $request->get('id')]
            );

        $productPrice = $prices[0];

        $handler = $this->get('oro_form.update_handler');
        $priceHandler = $this->get('oro_pricing.handler.product_price_handler');
        return $handler->update(
            $productPrice,
            PriceListProductPriceType::NAME,
            null,
            $request,
            $priceHandler,
            null
        );
    }

    /**
     * @Route("/get-matching-price", name="oro_pricing_matching_price")
     * @Method({"GET"})
     * @AclAncestor("oro_pricing_product_price_view")
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get('oro_pricing.provider.matching_price')->getMatchingPrices(
            $lineItems,
            $this->get('oro_pricing.model.price_list_request_handler')->getPriceListByCustomer()
        );

        return new JsonResponse($matchedPrices);
    }

    /**
     * @Route(
     *     "/delete-product-price/{priceListId}/{productPriceId}",
     *      name="oro_product_price_delete"
     *     )
     * @ParamConverter("priceList", class="OroPricingBundle:PriceList", options={"id" = "priceListId"})
     * @Acl(
     *      id="oro_pricing_product_price_delete",
     *      type="entity",
     *      class="OroPricingBundle:ProductPrice",
     *      permission="DELETE"
     * )
     *
     * {@inheritdoc}
     */
    public function deleteAction(PriceList $priceList, $productPriceId)
    {
        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->get('doctrine')
            ->getRepository(ProductPrice::class);
        /** @var ProductPrice $productPrice */
        $productPrice = $priceRepository
            ->findByPriceList(
                $this->get('oro_pricing.shard_manager'),
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
                $priceManager = $this->get('oro_pricing.manager.price_manager');
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
            'refreshGrid' => $this->get('oro_action.helper.context')->getActionData()->getRefreshGrid(),
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
        $translator = $this->get('translator');
        $result = [];

        foreach ($messages as $message) {
            $result[] = $translator->trans($message['message'], $message['parameters']);
        }

        return $result;
    }
}
