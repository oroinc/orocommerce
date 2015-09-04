<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Psr\Log\LoggerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\ShoppingListCreateRfpHandler;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListController extends Controller
{
    /**
     * @Route("/", name="orob2b_shopping_list_frontend_index")
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:index.html.twig")
     * @AclAncestor("orob2b_shopping_list_frontend_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_shopping_list.entity.shopping_list.class'),
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
            'entity' => $shoppingList,
            'formCreateRfp' => $this->getCreateRfpForm($shoppingList)->createView(),
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_shopping_list_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_shopping_list_frontend_view")
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
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);

        return $this->update($request, $shoppingList);
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
     * @AclAncestor("orob2b_shopping_list_frontend_update")
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
     * @Route("/create_rfp/{id}", name="orob2b_shopping_list_frontend_create_rfp", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_shopping_list_frontend_create_rfp",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @Template("OroB2BShoppingListBundle:ShoppingList/Frontend:view.html.twig")
     *
     * @param Request $request
     * @param ShoppingList $shoppingList
     * @return array|RedirectResponse
     */
    public function createRfpAction(Request $request, ShoppingList $shoppingList)
    {
        return $this->createRfp($request, $shoppingList);
    }

    /**
     * @param Request $request
     * @param ShoppingList $shoppingList
     * @return array|RedirectResponse
     */
    protected function createRfp(Request $request, ShoppingList $shoppingList)
    {
        /** @var ObjectManager $em */
        $em = $this->getDoctrine()->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $form = $this->getCreateRfpForm($shoppingList);
        $handler = new ShoppingListCreateRfpHandler(
            $form,
            $request,
            $em,
            $this->getUser(),
            $this->getDraftRequestStatus()
        );
        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $shoppingList,
                $form,
                function (ShoppingList $shoppingList) {
                    return [
                        'route'         => 'orob2b_shopping_list_frontend_view',
                        'parameters'    => ['id' => $shoppingList->getId()],
                    ];
                },
                function () use ($handler) {
                    return [
                        'route'         => 'orob2b_rfp_frontend_request_update',
                        'parameters'    => ['id' => $handler->getRfpRequest()->getId()],
                    ];
                },
                $this->getTranslator()->trans('orob2b.frontend.shoppinglist.message.create_rfp.success'),
                $handler,
                function (ShoppingList $entity, FormInterface $form) use ($handler) {
                    /* @var $session Session */
                    $session = $this->get('session');
                    $session->getFlashBag()->add(
                        'error',
                        $this->getTranslator()->trans('orob2b.frontend.shoppinglist.message.create_rfp.error')
                    );
                    if ($handler->getException()) {
                        $this->getLogger()->error($handler->getException());
                    }
                    return [
                        'entity' => $entity,
                        'formCreateRfp' => $form->createView(),
                    ];
                }
            );
    }

    /**
     * @param ShoppingList $shoppingList
     * @return Form
     */
    protected function getCreateRfpForm(ShoppingList $shoppingList = null)
    {
        return $this->createFormBuilder($shoppingList)->getForm();
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
     * @return RequestStatus
     */
    protected function getDraftRequestStatus()
    {
        $requestStatusClass = $this->container->getParameter('orob2b_rfp.entity.request.status.class');

        return $this
            ->getDoctrine()
            ->getManagerForClass($requestStatusClass)
            ->getRepository($requestStatusClass)
            ->findOneBy(['name' => RequestStatus::DRAFT])
        ;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->get('logger');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}
