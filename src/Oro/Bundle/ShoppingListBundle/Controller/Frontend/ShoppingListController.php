<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
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
     *
     * @param ShoppingList|null $shoppingList
     * @return array
     */
    public function viewAction(ShoppingList $shoppingList = null)
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
     *
     * @param ShoppingList $shoppingList
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return array|Response
     */
    public function moveMassActionAction(
        ShoppingList $shoppingList,
        Request $request,
        string $gridName,
        string $actionName
    ) {
        if ($request->getMethod() === Request::METHOD_GET) {
            return [
                'data' => [
                    'entity' => $shoppingList,
                ],
            ];
        }

        return $this->forward(
            'OroDataGridBundle:Grid:massAction',
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
                    'route' => 'oro_shopping_list_frontend_update',
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
            UpdateHandler::class,
            TranslatorInterface::class
        ]);
    }
}
