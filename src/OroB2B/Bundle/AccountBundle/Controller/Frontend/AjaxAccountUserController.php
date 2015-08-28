<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Controller\AbstractAjaxAccountUserController;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use Symfony\Component\Routing\Annotation\Route;

class AjaxAccountUserController extends AbstractAjaxAccountUserController
{
    /**
     * @Route("/confirm/{id}", name="orob2b_account_frontend_account_user_confirm", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_account_frontend_account_user_update")
     *
     * {@inheritdoc}
     */
    public function confirmAction(AccountUser $accountUser)
    {
        return parent::confirmAction($accountUser);
    }

    /**
     * Send confirmation email
     *
     * @Route(
     *      "/confirmation/send/{id}",
     *      name="orob2b_account_frontend_account_user_send_confirmation",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_account_frontend_account_user_update")
     *
     * {@inheritdoc}
     */
    public function sendConfirmationAction(AccountUser $accountUser)
    {
        return parent::sendConfirmationAction($accountUser);
    }

    /**
     * @Route(
     *      "/enable/{id}",
     *      name="orob2b_account_frontend_account_user_enable",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_account_frontend_account_user_update")
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
     *      name="orob2b_account_frontend_account_user_disable",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_account_frontend_account_user_update")
     *
     * {@inheritdoc}
     */
    public function disableAction(AccountUser $accountUser)
    {
        return parent::disableAction($accountUser);
    }

    /**
     * @Route("/get-account/{id}",
     *      name="orob2b_account_frontend_account_user_get_account",
     *      requirements={"id"="\d+"})
     * @AclAncestor("orob2b_account_frontend_account_user_view")
     *
     * {@inheritdoc}
     */
    public function getAccountIdAction(AccountUser $accountUser)
    {
        return parent::getAccountIdAction($accountUser);
    }
}
