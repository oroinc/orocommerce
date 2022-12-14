<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadProductTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const REFERENCE_PREFIX = 'product_tax_code';

    public const TAX_1 = 'TAX1';
    public const TAX_2 = 'TAX2';
    public const TAX_3 = 'TAX3';

    private const DATA = [
        self::TAX_1 => [
            'description' => 'Tax description 1',
            'products'    => [LoadProductData::PRODUCT_1, LoadProductData::PRODUCT_2]
        ],
        self::TAX_2 => [
            'description' => 'Tax description 2',
            'products'    => [LoadProductData::PRODUCT_3]
        ],
        self::TAX_3 => [
            'description' => 'Tax description 3',
            'products'    => []
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadProductData::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $organization = $this->getFirstUser($manager)->getOrganization();
        foreach (self::DATA as $code => $item) {
            $productTaxCode = new ProductTaxCode();
            $productTaxCode->setCode($code);
            $productTaxCode->setDescription($item['description']);
            $productTaxCode->setOrganization($organization);
            foreach ($item['products'] as $productRef) {
                /** @var Product $product */
                $product = $this->getReference($productRef);
                $product->setTaxCode($productTaxCode);
            }
            $manager->persist($productTaxCode);
            $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $productTaxCode);
        }
        $manager->flush();
    }
}
