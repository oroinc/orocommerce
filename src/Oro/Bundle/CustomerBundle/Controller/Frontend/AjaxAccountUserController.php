<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CustomerBundle\Controller\AbstractAjaxAccountUserController;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class AjaxAccountUserController extends AbstractAjaxAccountUserController
{
    /**
     * @Route("/get-account/{id}",
     *      name="oro_account_frontend_account_user_get_account",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_account_frontend_account_user_view")
     *
     * {@inheritdoc}
     */
    public function getAccountIdAction(AccountUser $accountUser)
    {
        return parent::getAccountIdAction($accountUser);
    }
}
