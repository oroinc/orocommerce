<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AjaxAccountUserController extends AbstractAjaxAccountUserController
{
    /**
     * @Route(
     *      "/enable/{id}",
     *      name="orob2b_account_account_user_enable",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_account_account_user_update")
     *
     * {@inheritdoc}
     */
    public function enableAction(AccountUser $accountUser)
    {
        return parent::enableAction($accountUser);
    }

    /**
     * @Route(
     *      "/disable/{id}",
     *      name="orob2b_account_account_user_disable",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_account_account_user_update")
     *
     * {@inheritdoc}
     */
    public function disableAction(AccountUser $accountUser)
    {
        return parent::disableAction($accountUser);
    }

    /**
     * @Route("/get-account/{id}",
     *      name="orob2b_account_account_user_get_account",
     *      requirements={"id"="\d+"})
     * @AclAncestor("orob2b_account_account_user_view")
     *
     * {@inheritdoc}
     */
    public function getAccountIdAction(AccountUser $accountUser)
    {
        return parent::getAccountIdAction($accountUser);
    }
}
