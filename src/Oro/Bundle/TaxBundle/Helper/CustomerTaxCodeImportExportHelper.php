<?php

namespace Oro\Bundle\TaxBundle\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;

class CustomerTaxCodeImportExportHelper
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var CustomerTaxCode[]
     */
    private array $customerTaxCodes = [];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Returns array [Customer::class => [CustomerTaxCode $object, CustomerTaxCode $object,...]]
     */
    public function loadCustomerTaxCode(array $customers): array
    {
        $customerTaxCodes = [];
        foreach ($customers as $customer) {
            $taxCode = $customer->getTaxCode();
            if (!isset($this->customerTaxCodes[$taxCode->getId()])) {
                $this->customerTaxCodes[$taxCode->getId()] = $taxCode;
            }

            $customerTaxCodes[$customer->getId()] = $this->customerTaxCodes[$taxCode->getId()];
        }

        return $customerTaxCodes;
    }

    /**
     * @param CustomerTaxCode $customerTaxCode
     * @return array
     */
    public function normalizeCustomerTaxCode(CustomerTaxCode $customerTaxCode = null)
    {
        if (!$customerTaxCode) {
            return ['code' => ''];
        }

        return ['code' => $customerTaxCode->getCode()];
    }

    /**
     * @param array $data
     * @return null|CustomerTaxCode
     * @throws EntityNotFoundException
     */
    public function denormalizeCustomerTaxCode(array $data)
    {
        if (!isset($data['tax_code'], $data['tax_code']['code'])) {
            return null;
        }

        $taxCodeCode = $data['tax_code']['code'];

        /** @var CustomerTaxCode $taxCode */
        $taxCode = $this->getCustomerTaxCodeRepository()
            ->findOneBy(['code' => $taxCodeCode]);

        if ($taxCode === null) {
            throw new EntityNotFoundException("Can't find CustomerTaxCode with code: \"{$taxCodeCode}\"");
        }

        return $taxCode;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|CustomerTaxCodeRepository
     */
    private function getCustomerTaxCodeRepository()
    {
        return $this->doctrineHelper->getEntityRepository(CustomerTaxCode::class);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(CustomerTaxCode::class);
    }
}
