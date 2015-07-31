<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountGroupHandler;


class AccountGroupController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_group_index")
     * @Template
     * @AclAncestor("orob2b_account_group_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_group.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_account_group_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="orob2b_account_group_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountGroup",
     *      permission="VIEW"
     * )
     * @Template()
     *
     * @param AccountGroup $group
     * @return array
     */
    public function viewAction(AccountGroup $group)
    {
        return [
            'entity' => $group
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_group_create")
     * @Template("OroB2BAccountBundle:AccountGroup:update.html.twig")
     * @Acl(
     *      id="orob2b_account_group_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountGroup",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new AccountGroup());
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_group_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_group_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountGroup",
     *      permission="EDIT"
     * )
     *
     * @param AccountGroup $group
     * @return array
     */
    public function updateAction(AccountGroup $group)
    {
        return $this->update($group);
    }

    /**
     * @param AccountGroup $group
     * @return array|RedirectResponse
     */
    protected function update(AccountGroup $group)
    {
        $form = $this->createForm(AccountGroupType::NAME, $group);
        $handler = new AccountGroupHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($group))
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $group,
            $form,
            function (AccountGroup $group) {
                return [
                    'route' => 'orob2b_account_group_update',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            function (AccountGroup $group) {
                return [
                    'route' => 'orob2b_account_group_view',
                    'parameters' => ['id' => $group->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.accountgroup.saved.message'),
            $handler
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_account_group_info", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountGroup/widget:info.html.twig")
     * @AclAncestor("orob2b_account_group_view")
     *
     * @param AccountGroup $group
     * @return array
     */
    public function infoAction(AccountGroup $group)
    {
        return [
            'entity' => $group
        ];
    }
}
