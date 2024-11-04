<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Tax::class === $entityClass) {
            $tax = new Tax();
            $tax->setCode('TEST_TAX');
            $tax->setDescription('Test Tax');
            $tax->setRate(1);
            $repository->setReference('tax', $tax);
            $em->persist($tax);
            $em->flush();

            return ['tax'];
        }

        if (TaxJurisdiction::class === $entityClass) {
            $taxJurisdiction = new TaxJurisdiction();
            $taxJurisdiction->setCode('TEST_JURISDICTION');
            $taxJurisdiction->setDescription('Test Jurisdiction');
            $taxJurisdiction->setCountry($em->find(Country::class, 'US'));
            $taxJurisdiction->setRegion($em->find(Region::class, 'US-CA'));
            $repository->setReference('taxJurisdiction', $taxJurisdiction);
            $em->persist($taxJurisdiction);
            $em->flush();

            return ['taxJurisdiction'];
        }

        if (CustomerTaxCode::class === $entityClass) {
            $customerTaxCode = new CustomerTaxCode();
            $customerTaxCode->setOrganization($repository->getReference('organization'));
            $customerTaxCode->setOwner($repository->getReference('user'));
            $customerTaxCode->setCode('TEST_CUSTOMER_TAX_CODE');
            $customerTaxCode->setDescription('Test Customer Tax Code');
            $repository->setReference('customerTaxCode', $customerTaxCode);
            $em->persist($customerTaxCode);
            $em->flush();

            return ['customerTaxCode'];
        }

        if (ProductTaxCode::class === $entityClass) {
            $productTaxCode = new ProductTaxCode();
            $productTaxCode->setOrganization($repository->getReference('organization'));
            $productTaxCode->setCode('TEST_PRODUCT_TAX_CODE');
            $productTaxCode->setDescription('Test Product Tax Code');
            $repository->setReference('productTaxCode', $productTaxCode);
            $em->persist($productTaxCode);
            $em->flush();

            return ['productTaxCode'];
        }

        if (TaxRule::class === $entityClass) {
            $tax = new Tax();
            $tax->setCode('TEST_TR_TAX');
            $tax->setRate(1);
            $em->persist($tax);
            $taxJurisdiction = new TaxJurisdiction();
            $taxJurisdiction->setCode('TEST_TR_JURISDICTION');
            $taxJurisdiction->setCountry($em->find(Country::class, 'US'));
            $taxJurisdiction->setRegion($em->find(Region::class, 'US-CA'));
            $em->persist($taxJurisdiction);
            $customerTaxCode = new CustomerTaxCode();
            $customerTaxCode->setOrganization($repository->getReference('organization'));
            $customerTaxCode->setOwner($repository->getReference('user'));
            $customerTaxCode->setCode('TEST_TR_CUSTOMER_TAX_CODE');
            $em->persist($customerTaxCode);
            $productTaxCode = new ProductTaxCode();
            $productTaxCode->setOrganization($repository->getReference('organization'));
            $productTaxCode->setCode('TEST_TR_PRODUCT_TAX_CODE');
            $em->persist($productTaxCode);
            $taxRule = new TaxRule();
            $taxRule->setOrganization($repository->getReference('organization'));
            $taxRule->setTax($tax);
            $taxRule->setTaxJurisdiction($taxJurisdiction);
            $taxRule->setCustomerTaxCode($customerTaxCode);
            $taxRule->setProductTaxCode($productTaxCode);
            $taxRule->setDescription('Test Tax Rule');
            $repository->setReference('taxRule', $taxRule);
            $em->persist($taxRule);
            $em->flush();

            return ['taxRule'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Tax::class === $entityClass) {
            return 'TEST_TAX';
        }
        if (TaxJurisdiction::class === $entityClass) {
            return 'TEST_JURISDICTION';
        }
        if (CustomerTaxCode::class === $entityClass) {
            return 'TEST_CUSTOMER_TAX_CODE';
        }
        if (ProductTaxCode::class === $entityClass) {
            return 'TEST_PRODUCT_TAX_CODE';
        }
        if (TaxRule::class === $entityClass) {
            return 'TEST_TR_TAX TEST_TR_JURISDICTION TEST_TR_PRODUCT_TAX_CODE TEST_TR_CUSTOMER_TAX_CODE';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
