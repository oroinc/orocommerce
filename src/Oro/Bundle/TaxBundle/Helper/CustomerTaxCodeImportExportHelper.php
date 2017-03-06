<?php

namespace Oro\Bundle\TaxBundle\Helper;

use Oro\Bundle\CustomerBundle\Entity\Customer;
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
     * @param DoctrineHelper $doctrineHelper
     */
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
            $customerTaxCodes[$customer->getId()] = $this->getCustomerTaxCodeRepository()
                ->findOneByCustomer($customer);
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
     * @return \Doctrine\ORM\EntityRepository|CustomerTaxCodeRepository
     */
    private function getCustomerTaxCodeRepository()
    {
        return $this->doctrineHelper->getEntityRepository(CustomerTaxCode::class);
    }
}
