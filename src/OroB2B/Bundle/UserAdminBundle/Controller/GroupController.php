<?php

namespace OroB2B\Bundle\UserAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;
use OroB2B\Bundle\UserAdminBundle\Form\Handler\GroupHandler;
use OroB2B\Bundle\UserAdminBundle\Form\Type\GroupType;

class GroupController extends Controller
{
    /**
     * @Route("/", name="orob2b_user_admin_group_index")
     * @Template
     * @Acl(
     *      id="orob2b_user_admin_group_view",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:Group",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_user_admin.group.entity.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_user_admin_group_create")
     * @Template("OroB2BUserAdminBundle:Group:update.html.twig")
     * @Acl(
     *      id="orob2b_user_admin_group_create",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:Group",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new Group(''));
    }

    /**
     * @Route("/update/{id}", name="orob2b_user_admin_group_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_user_admin_group_update",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:Group",
     *      permission="EDIT"
     * )
     * @return array|RedirectResponse
     */
    public function updateAction(Group $group)
    {
        return $this->update($group);
    }

    /**
     * @param Group $group
     * @return array|RedirectResponse
     */
    protected function update(Group $group)
    {
        $form = $this->createForm(GroupType::NAME);
        $handler = new GroupHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BUserAdminBundle:Group')
        );

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $group,
                $form,
                function (Group $group) {
                    return [
                        'route' => 'orob2b_user_admin_group_update',
                        'parameters' => [
                            'id' => $group->getId()
                        ]
                    ];
                },
                function () {
                    return [
                        'route' => 'orob2b_user_admin_group_index',
                    ];
                },
                $this->get('translator')->trans('orob2b.useradmin.message.group_saved'),
                $handler
            );
    }
}
