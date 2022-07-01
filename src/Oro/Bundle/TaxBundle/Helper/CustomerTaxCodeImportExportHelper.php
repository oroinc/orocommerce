<?php

namespace Oro\Bundle\TaxBundle\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;

/**
 * Help to collect tax code field of customer entity and return.
 */
class CustomerTaxCodeImportExportHelper
{
    private DoctrineHelper $doctrineHelper;

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
     *
     * @param Customer[] $customers
     * @return CustomerTaxCode[]
     */
    public function loadCustomerTaxCode(array $customers)
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

    public function normalizeCustomerTaxCode(CustomerTaxCode $customerTaxCode = null): array
    {
        if (!$customerTaxCode) {
            return ['code' => ''];
        }

        return ['code' => $customerTaxCode->getCode()];
    }

    /**
     * @throws EntityNotFoundException
     */
    public function denormalizeCustomerTaxCode(array $data): ?CustomerTaxCode
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
     * @return EntityRepository|CustomerTaxCodeRepository
     */
    private function getCustomerTaxCodeRepository()
    {
        return $this->doctrineHelper->getEntityRepository(CustomerTaxCode::class);
    }
}
