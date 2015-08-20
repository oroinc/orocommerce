<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("orob2b_api_account_")
 */
class AccountGroupController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete account group",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_account_group_delete",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountGroup",
     *      permission="DELETE"
     * )
     *
     * @param int $id
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
        return $this->get('orob2b_account.manager.group.api.attribute');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
