<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Routing\ClassResourceInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;

abstract class AbstractAccountUserAddressController extends RestController implements ClassResourceInterface
{
    /**
     * @param int $entityId
     * @return JsonResponse
     */
    public function cgetAction($entityId)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserManager()->find($entityId);
        $result  = [];

        if ($accountUser) {
            $items = $accountUser->getAddresses();

            foreach ($items as $item) {
                $result[] = $this->getPreparedItem($item);
            }
        }

        return new JsonResponse($result, $accountUser ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    /**
     * @param int $entityId
     * @param int $addressId
     * @return Response
     */
    public function deleteAction($entityId, $addressId)
    {
        /** @var AccountUserAddress $address */
        $address = $this->getManager()->find($addressId);
        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserManager()->find($entityId);
        if ($accountUser->getAddresses()->contains($address)) {
            $accountUser->removeAddress($address);
            return $this->handleDeleteRequest($addressId);
        } else {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }
    }

    /**
     * @return ApiEntityManager
     */
    protected function getAccountUserManager()
    {
        return $this->get('orob2b_account.account_user.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_account.account_user_address.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        // convert addresses to plain array
        $addressTypesData = [];

        /** @var $addressType AddressType */
        foreach ($entity->getTypes() as $addressType) {
            $addressTypesData[] = parent::getPreparedItem($addressType);
        }

        $addressDefaultsData = [];

        /** @var  $defaultType AddressType */
        foreach ($entity->getDefaults() as $defaultType) {
            $addressDefaultsData[] = parent::getPreparedItem($defaultType);
        }

        $result                = parent::getPreparedItem($entity);
        $result['types']       = $addressTypesData;
        $result['defaults']    = $addressDefaultsData;
        $result['countryIso2'] = $entity->getCountryIso2();
        $result['countryIso3'] = $entity->getCountryIso2();
        $result['regionCode']  = $entity->getRegionCode();
        $result['country'] = $entity->getCountryName();

        unset($result['frontendOwner']);

        return $result;
    }
}
