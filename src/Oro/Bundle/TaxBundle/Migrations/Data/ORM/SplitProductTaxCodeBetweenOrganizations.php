<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;

/**
 * Split existing product tax codes between organizations
 */
class SplitProductTaxCodeBetweenOrganizations extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [LoadProductTaxCodeOrganizationData::class];
    }

    public function load(ObjectManager $manager): void
    {
        $productTaxCodes = $this->getProductTaxCodes($manager);
        foreach ($this->getOrganizations($manager) as $organization) {
            $this->splitProductTaxCode($manager, $organization, $productTaxCodes);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param OrganizationInterface $organization
     * @param array $productTaxCodes
     */
    private function splitProductTaxCode(
        ObjectManager $manager,
        OrganizationInterface $organization,
        $productTaxCodes = []
    ): void {
        $productTaxCodeByOrganization = $this->getProductTaxCodes($manager, $organization);
        $diffProductTaxCode = array_diff($productTaxCodes, $productTaxCodeByOrganization);

        /** @var ProductTaxCode $productTaxCode */
        foreach ($diffProductTaxCode as $productTaxCode) {
            $productTaxCodeByOrganization = clone $productTaxCode;
            $productTaxCodeByOrganization->setOrganization($organization);
            $manager->persist($productTaxCodeByOrganization);
        }
    }

    private function getProductTaxCodes(ObjectManager $manager, OrganizationInterface $organization = null): array
    {
        /** @var ProductTaxCodeRepository $priceAttributeRepository */
        $priceAttributeRepository = $manager->getRepository(ProductTaxCode::class);
        /** @var QueryBuilder $qb */
        $qb = $priceAttributeRepository
            ->createQueryBuilder('oro_tax_product_tax_code')
            ->select('oro_tax_product_tax_code');
        if ($organization) {
            $qb
                ->where('oro_tax_product_tax_code.organization = :organization')
                ->setParameter('organization', $organization);
        }
        return $qb->getQuery()->getResult();
    }

    private function getOrganizations(ObjectManager $manager): array
    {
        return $manager->getRepository(Organization::class)->findAll();
    }
}
