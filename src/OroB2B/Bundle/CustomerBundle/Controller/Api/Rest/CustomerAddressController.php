<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Api\Rest;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @NamePrefix("orob2b_api_customer_")
 */
class CustomerAddressController extends RestController implements ClassResourceInterface
{
    /**
     * REST GET address
     *
     * @param int $entityId
     * @param string $addressId
     *
     * @ApiDoc(
     *      description="Get customer address",
     *      resource=true
     * )
     * @AclAncestor("orob2b_customer_view")
     * @return Response
     */
    public function getAction($entityId, $addressId)
    {
        /** @var Customer $customer */
        $customer = $this->getCustomerManager()->find($entityId);

        /** @var CustomerAddress $address */
        $address = $this->getManager()->find($addressId);

        $addressData = null;
        if ($address && $customer->getAddresses()->contains($address)) {
            $addressData = $this->getPreparedItem($address);
        }
        $responseData = $addressData ? json_encode($addressData) : '';
        return new Response($responseData, $address ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all addresses items",
     *      resource=true
     * )
     * @AclAncestor("orob2b_customer_view")
     * @param int $entityId
     *
     * @return JsonResponse
     */
    public function cgetAction($entityId)
    {
        /** @var Customer $customer */
        $customer = $this->getCustomerManager()->find($entityId);
        $result = [];

        if (!empty($customer)) {
            $items = $customer->getAddresses();

            foreach ($items as $item) {
                $result[] = $this->getPreparedItem($item);
            }
        }

        return new JsonResponse(
            $result,
            empty($customer) ? Codes::HTTP_NOT_FOUND : Codes::HTTP_OK
        );
    }

    /**
     * REST DELETE address
     *
     * @ApiDoc(
     *      description="Delete address items",
     *      resource=true
     * )
     * @AclAncestor("orob2b_customer_delete")
     * @param     $entityId
     * @param int $addressId
     *
     * @return Response
     */
    public function deleteAction($entityId, $addressId)
    {
        /** @var CustomerAddress $address */
        $address = $this->getManager()->find($addressId);
        /** @var Customer $customer */
        $customer = $this->getCustomerManager()->find($entityId);
        if ($customer->getAddresses()->contains($address)) {
            $customer->removeAddress($address);
            return $this->handleDeleteRequest($addressId);
        } else {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }
    }

    /**
     * REST GET address by type
     *
     * @param int $entityId
     * @param string $typeName
     *
     * @ApiDoc(
     *      description="Get customer address by type",
     *      resource=true
     * )
     * @AclAncestor("orob2b_customer_view")
     * @return Response
     */
    public function getByTypeAction($entityId, $typeName)
    {
        /** @var Customer $customer */
        $customer = $this->getCustomerManager()->find($entityId);

        if ($customer) {
            $address = $customer->getAddressByTypeName($typeName);
        } else {
            $address = null;
        }

        $responseData = $address ? json_encode($this->getPreparedItem($address)) : '';

        return new Response($responseData, $address ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * REST GET primary address
     *
     * @param int $entityId
     *
     * @ApiDoc(
     *      description="Get customer primary address",
     *      resource=true
     * )
     * @AclAncestor("orob2b_customer_view")
     * @return Response
     */
    public function getPrimaryAction($entityId)
    {
        /** @var Customer $customer */
        $customer = $this->getCustomerManager()->find($entityId);

        if ($customer) {
            $address = $customer->getPrimaryAddress();
        } else {
            $address = null;
        }

        $responseData = $address ? json_encode($this->getPreparedItem($address)) : '';

        return new Response($responseData, $address ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * @return \Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager
     */
    protected function getCustomerManager()
    {
        return $this->get('orob2b_customer.manager.customer.api.attribute');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_customer.customer_address.manager.api');
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

        unset($result['owner']);

        return $result;
    }
}
