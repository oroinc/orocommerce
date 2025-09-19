<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use Oro\Bundle\PricingBundle\Handler\ProductPriceHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Adds actions to update, delete and get prices by customer or matching prices via AJAX
 */
class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/get-product-prices-by-customer', name: 'oro_pricing_price_by_customer', methods: ['GET'])]
    public function getProductPricesByCustomerAction(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    /**
     * Edit product form
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(
        path: '/update/{priceList}/{id}',
        name: 'oro_product_price_update_widget',
        requirements: ['priceListId' => '\d+']
    )]
    #[Template('@OroPricing/ProductPrice/widget/update.html.twig')]
    #[Acl(id: 'oro_pricing_product_price_update', type: 'entity', class: ProductPrice::class, permission: 'EDIT')]
    public function updateAction(Request $request)
    {
        $priceList = $this->container->get('doctrine')->getRepository(PriceList::class)
            ->find($request->get('priceList'));
        $prices = $this->container->get('doctrine')->getRepository(ProductPrice::class)
            ->findByPriceList(
                $this->container->get(ShardManager::class),
                $priceList,
                ['id' => $request->get('id')]
            );

        $productPrice = $prices[0];

        $this->denyAccessUnlessGranted('EDIT', $productPrice);

        $handler = $this->container->get(UpdateHandlerFacade::class);
        $priceHandler = $this->container->get(ProductPriceHandler::class);
        return $handler->update(
            $productPrice,
            PriceListProductPriceType::class,
            null,
            $request,
            $priceHandler,
            null
        );
    }

    #[Route(
        path: '/delete-product-price/{priceListId}/{productPriceId}',
        name: 'oro_product_price_delete',
        methods: ['DELETE']
    )]
    #[Acl(id: 'oro_pricing_product_price_delete', type: 'entity', class: ProductPrice::class, permission: 'DELETE')]
    #[CsrfProtection()]
    public function deleteAction(
        Request $request,
        #[MapEntity(id: 'priceListId')]
        PriceList $priceList,
        $productPriceId
    ) {
        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->container->get('doctrine')->getRepository(ProductPrice::class);
        /** @var ProductPrice $productPrice */
        $productPrice = $priceRepository
            ->findByPriceList(
                $this->container->get(ShardManager::class),
                $priceList,
                ['id' => $productPriceId]
            );
        $code = JsonResponse::HTTP_OK;
        $message = '';

        if (empty($productPrice)) {
            $code = JsonResponse::HTTP_NOT_FOUND;
        } else {
            try {
                $priceManager = $this->container->get(PriceManager::class);
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
            'refreshGrid' => $this->container->get(ContextHelper::class)->getActionData()->getRefreshGrid(),
            'flashMessages' => $request->getSession()->getFlashBag()->all()
        ];

        return new JsonResponse($response, $code);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ShardManager::class,
                UpdateHandlerFacade::class,
                ProductPriceHandler::class,
                ProductPriceScopeCriteriaRequestHandler::class,
                PriceManager::class,
                ContextHelper::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
