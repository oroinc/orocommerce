<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("orob2b_api_account_frontend_")
 */
class AccountUserController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete account user",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_account_frontend_account_user_delete",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="DELETE",
     *      group_name="commerce"
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
        return $this->get('orob2b_account.account_user.manager.api');
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
