<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListController extends Controller
{
    /**
     * @Route("/{id}", name="oro_shopping_list_frontend_view", defaults={"id" = null}, requirements={"id"="\d+"})
     * @Layout(vars={"title"})
     * @Acl(
     *      id="oro_shopping_list_frontend_view",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param int|null $id
     *
     * @return array
     */
    public function viewAction($id = null)
    {
        /** @var ShoppingListRepository $repo */
        $repo = $this->getDoctrine()->getRepository('OroShoppingListBundle:ShoppingList');
        $shoppingList = $repo->findOneByIdWithRelations($id);

        if (!$shoppingList) {
            $user = $this->getUser();
            if ($user instanceof AccountUser) {
                $shoppingList = $repo->findAvailableForAccountUser($user, true);
            }
        }
        if ($shoppingList) {
            $title = $shoppingList->getLabel();
            $totalWithSubtotalsAsArray = $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($shoppingList);

            $lineItems = $shoppingList->getLineItems();

            if (!empty($lineItems)) {
                $products = [];
                foreach ($lineItems as $lineItem) {
                    /** @var LineItem $lineItem */
                    $products[]['productSku'] = $lineItem->getProduct()->getSku();
                }
                if ($this->container->has('oro_rfp.form.type.extension.frontend_request_data_storage')) {
                    $shoppingList->setIsAllowedRFP(
                        $this->container
                            ->get('oro_rfp.form.type.extension.frontend_request_data_storage')
                            ->isAllowedRFP($products)
                    );
                }
            }
        } else {
            $title = null;
            $totalWithSubtotalsAsArray = [];
        }

        return [
            'title' => $title,
            'data' => [
                'entity' => $shoppingList,
                'totals' => [
                    'identifier' => 'totals',
                    'data' => $totalWithSubtotalsAsArray
                ]
            ],
        ];
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
        $shoppingListManager = $this->get('oro_shopping_list.shopping_list.manager');
        $shoppingList = $shoppingListManager->create();

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
        $form = $this->createForm(ShoppingListType::NAME);

        $handler = new ShoppingListHandler(
            $form,
            $request,
            $this->get('oro_shopping_list.shopping_list.manager'),
            $this->getDoctrine()
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $shoppingList,
            $this->createForm(ShoppingListType::NAME, $shoppingList),
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
            $this->get('translator')->trans('oro.shoppinglist.controller.shopping_list.saved.message'),
            $handler
        );
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('oro_pricing.subtotal_processor.total_processor_provider');
    }
}
