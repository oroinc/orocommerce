<?php

namespace Oro\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use Oro\Bundle\AccountBundle\Entity\AccountUserRole;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("oro_api_frontend_account_")
 * @RouteResource("accountuserrole")
 */
class AccountUserRoleController extends RestController implements ClassResourceInterface
{
    /**
     * @Route("/delete/{id}", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Delete account user role",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_account_frontend_account_user_role_delete_action",
     *      type="entity",
     *      class="OroAccountBundle:AccountUserRole",
     *      permission="FRONTEND_ACCOUNT_ROLE_DELETE",
     *      group_name="commerce"
     * )
     *
     * @param AccountUserRole $id
     * @return Response
     */
    public function deleteAction(AccountUserRole $id)
    {
        return $this->handleDeleteRequest($id->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_account.account_user_role.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
