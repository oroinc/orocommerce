<?php

namespace OroB2B\Bundle\UserAdminBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("frontendgroup")
 * @NamePrefix("orob2b_api_user_admin_")
 */
class GroupController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete frontend group",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_user_admin_group_delete",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:Group",
     *      permission="DELETE"
     * )
     * @param integer $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_user_admin.group.manager.api');
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
