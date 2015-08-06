<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;

class AccountController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_index")
     * @Template
     * @AclAncestor("orob2b_account_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_account_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:Account",
     *      permission="VIEW"
     * )
     *
     * @param Account $account
     * @return array
     */
    public function viewAction(Account $account)
    {
        return [
            'entity' => $account
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_create")
     * @Template("OroB2BAccountBundle:Account:update.html.twig")
     * @Acl(
     *      id="orob2b_account_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:Account",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new Account());
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:Account",
     *      permission="EDIT"
     * )
     *
     * @param Account $account
     * @return array
     */
    public function updateAction(Account $account)
    {
        return $this->update($account);
    }

    /**
     * @param Account $account
     * @return array|RedirectResponse
     */
    protected function update(Account $account)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $account,
            $this->createForm(AccountType::NAME, $account),
            function (Account $account) {
                return [
                    'route' => 'orob2b_account_update',
                    'parameters' => ['id' => $account->getId()]
                ];
            },
            function (Account $account) {
                return [
                    'route' => 'orob2b_account_view',
                    'parameters' => ['id' => $account->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.account.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_account_info", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:Account/widget:info.html.twig")
     * @AclAncestor("orob2b_account_view")
     *
     * @param Account $account
     * @return array
     */
    public function infoAction(Account $account)
    {
        return [
            'entity' => $account,
            'treeData' => $this->get('orob2b_account.account_tree_handler')->createTree($account->getId())
        ];
    }
}
