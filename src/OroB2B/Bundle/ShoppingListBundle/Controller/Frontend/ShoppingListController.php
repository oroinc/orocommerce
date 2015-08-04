<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class ShoppingListController extends Controller
{
    /**
     * @Route("/", name="orob2b_shopping_list_frontend_index")
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:index.html.twig")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_shopping_list.entity.shopping_list.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_shopping_list_frontend_view", requirements={"id"="\d+"})
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:view.html.twig")
     * @Acl(
     *      id="orob2b_shopping_list_frontend_view",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param ShoppingList $shoppingList
     *
     * @return array
     */
    public function viewAction(ShoppingList $shoppingList)
    {
        return [
            'entity' => $shoppingList
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_shopping_list_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend/widget:info.html.twig")
     *
     * @param ShoppingList $shoppingList
     *
     * @return array
     */
    public function infoAction(ShoppingList $shoppingList)
    {
        return [
            'shopping_list' => $shoppingList
        ];
    }

    /**
     * Create shopping list form
     *
     * @Route("/create", name="orob2b_shopping_list_frontend_create")
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_shopping_list_frontend_create",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $shoppingList = new ShoppingList();
        /** @var AccountUser $accountUser */
        $accountUser = $this->getUser();
        $shoppingList
            ->setOwner($accountUser)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getCustomer())
            ->setAccountUser($accountUser);

        return $this->update($request, $shoppingList);
    }

    /**
     * Create default shopping list
     *
     * @Route("/create-default", name="orob2b_shopping_list_frontend_create_default")
     * @AclAncestor("orob2b_shopping_list_frontend_create")
     *
     * @return JsonResponse
     */
    public function createDefaultAction()
    {
        $shoppingList = $this->get('orob2b_shopping_list.shopping_list.manager')->createCurrent();

        return new JsonResponse(['shoppingListId' => $shoppingList->getId()]);
    }

    /**
     * Check if shopping list exists
     *
     * @Route("/check-existing/{shoppingListId}", name="orob2b_shopping_list_frontend_check_existing")
     * @AclAncestor("orob2b_shopping_list_frontend_view")
     *
     * @param int $shoppingListId
     *
     * @return JsonResponse
     */
    public function checkExistingAction($shoppingListId = null)
    {
        $shoppingList = $this->getShoppingListRepository()->find((int) $shoppingListId);
        if (!$shoppingList) {
            $shoppingList = $this->get('orob2b_shopping_list.shopping_list.manager')->createCurrent();
        }

        return new JsonResponse(['shoppingListId' => $shoppingList->getId()]);
    }

    /**
     * Edit account user form
     *
     * @Route("/update/{id}", name="orob2b_shopping_list_frontend_update", requirements={"id"="\d+"})
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_shopping_list_frontend_update",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param ShoppingList $shoppingList
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, ShoppingList $shoppingList)
    {
        return $this->update($request, $shoppingList);
    }

    /**
     * @Route("/set-current/{id}", name="orob2b_shopping_list_frontend_set_current", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_shopping_list_frontend_set_current",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="EDIT"
     * )
     *
     * @param ShoppingList $shoppingList
     *
     * @return RedirectResponse
     */
    public function setCurrentAction(ShoppingList $shoppingList)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getUser();
        $this->get('orob2b_shopping_list.shopping_list.manager')->setCurrent(
            $accountUser,
            $shoppingList
        );
        $message = $this->get('translator')->trans('orob2b.shoppinglist.controller.shopping_list.saved.message');
        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->redirect(
            $this->generateUrl('orob2b_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );
    }

    /**
     * @param Request $request
     * @param ShoppingList $shoppingList
     *
     * @return array|RedirectResponse
     */
    protected function update(Request $request, ShoppingList $shoppingList)
    {
        $form = $this->createForm(ShoppingListType::NAME);

        $handler = new ShoppingListHandler(
            $form,
            $request,
            $this->get('orob2b_shopping_list.shopping_list.manager'),
            $this->getDoctrine()
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $shoppingList,
            $this->createForm(ShoppingListType::NAME, $shoppingList),
            function (ShoppingList $shoppingList) {
                return [
                    'route' => 'orob2b_shopping_list_frontend_update',
                    'parameters' => ['id' => $shoppingList->getId()]
                ];
            },
            function (ShoppingList $shoppingList) {
                return [
                    'route' => 'orob2b_shopping_list_frontend_view',
                    'parameters' => ['id' => $shoppingList->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.shoppinglist.controller.shopping_list.saved.message'),
            $handler
        );
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getShoppingListRepository()
    {
        return $this->getDoctrine()->getRepository('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
    }
}
