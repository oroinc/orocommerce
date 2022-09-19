<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Controller\GridController;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
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
     * @AclAncestor("oro_shopping_list_frontend_view")
     */
    public function viewAction(ShoppingList $shoppingList = null): array
    {
        if (!$shoppingList) {
            $shoppingList = $this->get(CurrentShoppingListManager::class)->getCurrent();
        }

        if ($shoppingList) {
            $this->get(ShoppingListManager::class)->actualizeLineItems($shoppingList);
        }

        return [
            'data' => [
                'entity' => $shoppingList,
            ],
        ];
    }

    /**
     * @Route("/all", name="oro_shopping_list_frontend_index")
     * @Layout
     * @AclAncestor("oro_shopping_list_frontend_view")
     */
    public function indexAction(): array
    {
        if (!$this->getUser() instanceof CustomerUser) {
            throw $this->createAccessDeniedException();
        }

        return [];
    }

    /**
     * @Route(
     *     "/update/{id}",
     *     name="oro_shopping_list_frontend_update",
     *     defaults={"id" = null},
     *     requirements={"id"="\d+"}
     * )
     * @Layout
     * @AclAncestor("oro_shopping_list_frontend_update")
     */
    public function updateAction(ShoppingList $shoppingList = null): array
    {
        if (!$shoppingList) {
            $shoppingList = $this->get(CurrentShoppingListManager::class)->getCurrent();
        }

        if ($shoppingList) {
            $this->get(ShoppingListManager::class)->actualizeLineItems($shoppingList);
        }

        return [
            'data' => [
                'entity' => $shoppingList,
            ],
        ];
    }

    /**
     * @Route(
     *      "/{id}/massAction/{gridName}/{actionName}",
     *      name="oro_shopping_list_frontend_move_mass_action",
     *      requirements={"id"="\d+", "gridName"="[\w\:\-]+", "actionName"="[\w\-]+"}
     * )
     * @Layout
     * @AclAncestor("oro_shopping_list_frontend_update")
     */
    public function moveMassActionAction(
        ShoppingList $shoppingList,
        Request $request,
        string $gridName,
        string $actionName
    ): array|Response {
        if ($request->getMethod() === Request::METHOD_GET) {
            return [
                'data' => [
                    'entity' => $shoppingList,
                ],
            ];
        }

        return $this->forward(
            GridController::class . '::massActionAction',
            ['gridName' => $gridName, 'actionName' => $actionName],
            $request->query->all()
        );
    }

    /**
     * @Route("/{id}/assign", name="oro_shopping_list_frontend_assign", requirements={"id"="\d+"})
     * @Layout
     * @AclAncestor("oro_shopping_list_frontend_assign")
     */
    public function assignAction(ShoppingList $shoppingList): array
    {
        return [
            'data' => [
                'entity' => $shoppingList
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
     */
    public function createAction(Request $request): array|Response
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
    
    protected function create(Request $request, ShoppingList $shoppingList): array|Response
    {
        $handler = new ShoppingListHandler(
            $this->get(CurrentShoppingListManager::class),
            $this->getDoctrine()
        );

        return $this->get(UpdateHandlerFacade::class)->update(
            $shoppingList,
            $this->createForm(ShoppingListType::class, $shoppingList),
            $this->get(TranslatorInterface::class)->trans('oro.shoppinglist.controller.shopping_list.saved.message'),
            $request,
            $handler
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            CurrentShoppingListManager::class,
            ShoppingListManager::class,
            TranslatorInterface::class,
            UpdateHandlerFacade::class
        ]);
    }
}
