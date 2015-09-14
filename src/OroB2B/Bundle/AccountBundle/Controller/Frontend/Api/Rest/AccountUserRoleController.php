<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("orob2b_api_frontend_account_")
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
     *      id="orob2b_account_frontend_account_user_role_delete",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
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
        return $this->get('orob2b_account.account_user_role.manager.api');
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
