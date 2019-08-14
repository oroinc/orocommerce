<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @return array
     *
     */
    public function viewAction(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            $shoppingList = $this->get(CurrentShoppingListManager::class)->getCurrent();
        }

        if ($shoppingList) {
            $title = $shoppingList->getLabel();
            $totalWithSubtotalsAsArray = $this->get(TotalProcessorProvider::class)
                ->getTotalWithSubtotalsAsArray($shoppingList);
        } else {
            $title = null;
            $totalWithSubtotalsAsArray = [];
        }

        return [
            'data' => [
                'title' => $title,
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
        ]);
    }
}
