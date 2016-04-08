<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Controller\Api\Rest\AbstractAccountUserAddressController;

/**
 * @NamePrefix("orob2b_api_account_frontend_")
 */
class AccountUserAddressController extends AbstractAccountUserAddressController
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all addresses items",
     *      resource=true
     * )
     * @AclAncestor("orob2b_account_account_user_view")
     * @param int $entityId
     *
     * @return JsonResponse
     */
    public function cgetAction($entityId)
    {
        return parent::cgetAction($entityId);
    }

    /**
     * REST DELETE address
     *
     * @ApiDoc(
     *      description="Delete address items",
     *      resource=true
     * )
     * @AclAncestor("orob2b_account_account_user_delete")
     * @param int $entityId
     * @param int $addressId
     *
     * @return Response
     */
    public function deleteAction($entityId, $addressId)
    {
        return parent::deleteAction($entityId, $addressId);
    }
}
