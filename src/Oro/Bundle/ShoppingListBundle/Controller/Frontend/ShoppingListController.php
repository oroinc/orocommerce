<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller that manages shopping lists.
 */
class ShoppingListController extends AbstractController
{
    /**
     * @Route("/{id}", name="oro_shopping_list_frontend_view", defaults={"id" = null}, requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="oro_shopping_list_frontend_view",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param ShoppingList $shoppingList
     * @return array|Response
     */
    public function viewAction(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            $shoppingList = $this->get(CurrentShoppingListManager::class)->getCurrent();
        }

        if ($shoppingList) {
            $this->get(ShoppingListManager::class)->actualizeLineItems($shoppingList);

            $shoppingList = $this->getDoctrine()->getManagerForClass(ShoppingList::class)
                ->getRepository(ShoppingList::class)
                ->findForViewAction($shoppingList->getId());

            $title = $shoppingList->getLabel();
            $lineItems = $shoppingList->getLineItems()->toArray();

            $frontendProductPricesDataProvider = $this->get(FrontendProductPricesDataProvider::class);

            // All prices must be fetched before matched prices to enable more efficient caching (i.e. all prices
            // already contain matched prices, so they will be returned without new DB queries)
            $productPrices = $frontendProductPricesDataProvider->getProductsAllPrices($lineItems);
            $allPrices = $this->get(ProductPriceFormatter::class)->formatProducts($productPrices);

            $matchedPrice = $frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);

            $totalWithSubtotalsAsArray = $this->get(TotalProcessorProvider::class)
                ->getTotalWithSubtotalsAsArray($shoppingList);

            $buttons = $this->getButtons($shoppingList);
        } else {
            $title = null;
            $totalWithSubtotalsAsArray = [];
            $allPrices = [];
            $matchedPrice = [];
            $buttons = [];
        }

        return [
            'data' => [
                'title' => $title,
                'entity' => $shoppingList,
                'totals' => [
                    'identifier' => 'totals',
                    'data' => $totalWithSubtotalsAsArray
                ],
                'shopping_list_buttons' => ['data' => $buttons],
                'all_prices' => ['data' => $allPrices],
                'matched_price' => ['data' => $matchedPrice],
            ],
        ];
    }

    /**
     * @param ShoppingList $shoppingList
     *
     * @return array
     */
    private function getButtons(ShoppingList $shoppingList): array
    {
        return $this->get(ButtonProvider::class)->findAvailable(
            $this->get(ButtonSearchContextProvider::class)
                ->getButtonSearchContext()
                ->setEntity(ShoppingList::class, ['id' => $shoppingList->getId()])
        );
    }

    /**
     * @Route("/my", name="oro_shopping_list_frontend_index")
     * @Layout
     * @AclAncestor("oro_shopping_list_frontend_view")
     *
     * @return array
     */
    public function indexAction(): array
    {
        return [];
    }

    /**
     * Create shopping list form
     *
     * @Route("/create", name="oro_shopping_list_frontend_create")
     * @Layout
     * @Acl(
     *      id="oro_shopping_list_frontend_create",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @return array|Response
     */
    public function createAction(Request $request)
    {
        $shoppingList = $this->get(ShoppingListManager::class)->create();

        $response = $this->create($request, $shoppingList);
        if ($response instanceof Response) {
            return $response;
        }

        $defaultResponse = [
            'savedId' => null,
            'shoppingList' => $shoppingList,
            'createOnly' => $request->get('createOnly')
        ];

        return ['data' => array_merge($defaultResponse, $response)];
    }

    /**
     * @param Request $request
     * @param ShoppingList $shoppingList
     *
     * @return array|Response
     */
    protected function create(Request $request, ShoppingList $shoppingList)
    {
        $form = $this->createForm(ShoppingListType::class);

        $handler = new ShoppingListHandler(
            $form,
            $request,
            $this->get(CurrentShoppingListManager::class),
            $this->getDoctrine()
        );

        return $this->get(UpdateHandler::class)->handleUpdate(
            $shoppingList,
            $this->createForm(ShoppingListType::class, $shoppingList),
            function (ShoppingList $shoppingList) {
                return [
                    'route' => 'oro_shopping_list_frontend_view',
                    'parameters' => ['id' => $shoppingList->getId()]
                ];
            },
            function (ShoppingList $shoppingList) {
                return [
                    'route' => 'oro_shopping_list_frontend_view',
                    'parameters' => ['id' => $shoppingList->getId()]
                ];
            },
            $this->get(TranslatorInterface::class)->trans('oro.shoppinglist.controller.shopping_list.saved.message'),
            $handler
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            CurrentShoppingListManager::class,
            ShoppingListManager::class,
            TotalProcessorProvider::class,
            UpdateHandler::class,
            TranslatorInterface::class,
            ButtonProvider::class,
            ButtonSearchContextProvider::class,
            FrontendProductPricesDataProvider::class,
            ProductPriceFormatter::class,
        ]);
    }
}
