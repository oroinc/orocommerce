<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Yaml\Yaml;

class LoadBrandData extends LoadProductData implements DependentFixtureInterface, ContainerAwareInterface
{
    use MakeProductAttributesTrait;
    use UserUtilityTrait;

    const BRAND_1 = 'brand-1';
    const BRAND_2 = 'brand-2';

    const BRAND_1_DEFAULT_NAME = 'brand-1.names.default';
    const BRAND_2_DEFAULT_NAME = 'brand-2.names.default';

    const BRAND_1_DEFAULT_DESCRIPTION = 'brand-1.description.default';
    const BRAND_2_DEFAULT_DESCRIPTION = 'brand-2.description.default';

    const BRAND_1_DEFAULT_SHORT_DESCRIPTION = 'brand-1.short_description.default';
    const BRAND_2_DEFAULT_SHORT_DESCRIPTION = 'brand-2.short_description.default';

    const BRAND_1_DEFAULT_SLUG_PROTOTYPE = 'brand-1.slugPrototypes.default';
    const BRAND_2_DEFAULT_SLUG_PROTOTYPE = 'brand-2.slugPrototypes.default';

    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData'
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->makeBrandFilterable();

        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();
        $businessUnit1 = $user->getOwner();

        $businessUnit2 = (new BusinessUnit())
            ->setName('TestBU')
            ->setOrganization($organization);
        $manager->persist($businessUnit2);

        $this->setReference('main-bu', $businessUnit1);
        $this->setReference('test-bu', $businessUnit2);

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'brand_fixture.yml';

        $data = Yaml::parse(file_get_contents($filePath));

        foreach ($data as $key => $item) {
            $brand = new Brand();
            $brand
                ->setOwner($this->getReference($item['businessUnit']))
                ->setOrganization($organization)
                ->setStatus($item['status']);

            $this->addAdvancedValue($item, $brand);
            $this->setReference($item['reference'], $brand);

            $manager->persist($brand);
        }

        $manager->flush();
    }

    private function makeBrandFilterable()
    {
        $this->updateProductAttributes(
            [
                'brand' => [
                    'filterable' => true,
                ],
            ]
        );

        $this->getConfigManager()->flush();
    }

    private function addAdvancedValue(array $item, Brand $brand)
    {
        if (!empty($item['names'])) {
            foreach ($item['names'] as $slugPrototype) {
                $brand->addName($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['slugPrototypes'])) {
            foreach ($item['slugPrototypes'] as $slugPrototype) {
                $brand->addSlugPrototype($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['descriptions'])) {
            foreach ($item['descriptions'] as $slugPrototype) {
                $brand->addDescription($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['shortDescriptions'])) {
            foreach ($item['shortDescriptions'] as $slugPrototype) {
                $brand->addShortDescription($this->createValue($slugPrototype));
            }
        }
    }
}
