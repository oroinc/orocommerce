<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

class LoadProductTaxCodesWithAdditionalOrganization extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';
    const TAX_3 = 'TAX3';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';
    const DESCRIPTION_3 = 'Tax description 3';

    const REFERENCE_PREFIX = 'product_tax_code';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->hasReference('acme_organization')) {
            $this->createAdditionalOrganization($manager);
        }
        /** @var Organization $organizationAcme */
        $organizationAcme = $this->getReference('acme_organization');

        $this->createProductTaxCode(
            $manager,
            self::TAX_1,
            self::DESCRIPTION_1,
            [LoadProductData::PRODUCT_1, LoadProductData::PRODUCT_2]
        );
        $this->createProductTaxCode($manager, self::TAX_2, self::DESCRIPTION_2, [LoadProductData::PRODUCT_3]);
        $this->createProductTaxCode($manager, self::TAX_3, self::DESCRIPTION_3, [], $organizationAcme);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param string $description
     * @param array $productRefs
     * @param OrganizationInterface|null $organization
     * @return ProductTaxCode
     */
    protected function createProductTaxCode(
        ObjectManager $manager,
        $code,
        $description,
        $productRefs,
        OrganizationInterface $organization = null
    ) {
        /** @var User $user */
        $user = $this->getFirstUser($manager);

        if (null === $organization) {
            /** @var OrganizationInterface $organization */
            $organization = $user->getOrganization();
        }

        $productTaxCode = new ProductTaxCode();
        $productTaxCode->setCode($code);
        $productTaxCode->setDescription($description);
        $productTaxCode->setOrganization($organization);
        foreach ($productRefs as $productRef) {
            /** @var Product $product */
            $product = $this->getReference($productRef);
            $product->setTaxCode($productTaxCode);
        }

        $manager->persist($productTaxCode);
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $productTaxCode);

        return $productTaxCode;
    }

    protected function createAdditionalOrganization(ObjectManager $manager)
    {
        $organization = new Organization();
        $organization->setName('acme_organization');
        $organization->setEnabled(true);

        $this->setReference('acme_organization', $organization);

        $manager->persist($organization);
    }
}
