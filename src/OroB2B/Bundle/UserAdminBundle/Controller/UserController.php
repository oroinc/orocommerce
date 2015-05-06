<?php

namespace OroB2B\Bundle\UserAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\UserAdminBundle\Entity\User;
use OroB2B\Bundle\UserAdminBundle\Form\Type\UserType;
use OroB2B\Bundle\UserAdminBundle\Form\Handler\UserHandler;

class UserController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_user_admin_user_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_user_admin_user_view",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:User",
     *      permission="VIEW"
     * )
     *
     * @param User $user
     * @return array
     */
    public function viewAction(User $user)
    {
        return [
            'entity' => $user
        ];
    }

    /**
     * @Route("/", name="orob2b_user_admin_user_index")
     * @Template
     * @AclAncestor("orob2b_user_admin_user_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_user_admin.user.entity.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_user_admin_user_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_user_admin_user_view")
     *
     * @param User $user
     * @return array
     */
    public function infoAction(User $user)
    {
        return [
            'entity' => $user
        ];
    }

    /**
     * Create user form
     *
     * @Route("/create", name="orob2b_user_admin_user_create")
     * @Template("OroB2BUserAdminBundle:User:update.html.twig")
     * @Acl(
     *      id="orob2b_user_admin_user_create",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:User",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new User());
    }

    /**
     * Edit user form
     *
     * @Route("/update/{id}", name="orob2b_user_admin_user_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_user_admin_user_update",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:User",
     *      permission="EDIT"
     * )
     * @param User $user
     * @return array|RedirectResponse
     */
    public function updateAction(User $user)
    {
        return $this->update($user);
    }

    /**
     * @param User $user
     * @return array|RedirectResponse
     */
    protected function update(User $user)
    {
        $form = $this->createForm(UserType::NAME, $user);
        $handler = new UserHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BCatalogBundle:Category')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $user,
            $form,
            function (User $user) {
                return array(
                    'route' => 'orob2b_user_admin_user_update',
                    'parameters' => array('id' => $user->getId())
                );
            },
            function () {
                return array(
                    'route' => 'orob2b_user_admin_user_index',
                );
            },
            $this->get('translator')->trans('orob2b.useradmin.controller.user.saved.message'),
            $handler
        );

        return $result;
    }
}
