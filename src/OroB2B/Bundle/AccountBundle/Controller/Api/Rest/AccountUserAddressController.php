<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;

/**
 * @NamePrefix("orob2b_api_account_")
 */
class AccountUserAddressController extends AbstractAccountUserAddressController
{
    /**
     * REST GET address
     *
     * @param int $entityId
     * @param string $addressId
     *
     * @ApiDoc(
     *      description="Get account user address",
     *      resource=true
     * )
     * @AclAncestor("orob2b_account_account_user_view")
     * @return Response
     */
    public function getAction($entityId, $addressId)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserManager()->find($entityId);

        /** @var AccountAddress $address */
        $address = $this->getManager()->find($addressId);

        $addressData = null;
        if ($address && $accountUser->getAddresses()->contains($address)) {
            $addressData = $this->getPreparedItem($address);
        }
        $responseData = $addressData ? json_encode($addressData) : '';
        return new Response($responseData, $address ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

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

    /**
     * REST GET address by type
     *
     * @param int $entityId
     * @param string $typeName
     *
     * @ApiDoc(
     *      description="Get account user address by type",
     *      resource=true
     * )
     * @AclAncestor("orob2b_account_account_user_view")
     * @return Response
     */
    public function getByTypeAction($entityId, $typeName)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserManager()->find($entityId);

        if ($accountUser) {
            $address = $accountUser->getAddressByTypeName($typeName);
        } else {
            $address = null;
        }

        $responseData = $address ? json_encode($this->getPreparedItem($address)) : '';

        return new Response($responseData, $address ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    /**
     * REST GET primary address
     *
     * @param int $entityId
     *
     * @ApiDoc(
     *      description="Get account user primary address",
     *      resource=true
     * )
     * @AclAncestor("orob2b_account_account_user_view")
     * @return Response
     */
    public function getPrimaryAction($entityId)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserManager()->find($entityId);

        if ($accountUser) {
            $address = $accountUser->getPrimaryAddress();
        } else {
            $address = null;
        }

        $responseData = $address ? json_encode($this->getPreparedItem($address)) : '';

        return new Response($responseData, $address ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }
}
