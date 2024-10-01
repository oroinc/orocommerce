<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads products kits for main organization.
 */
class LoadProductKitForAdditionalOrganizationData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const ADDITIONAL_ORGANIZATION = 'additional-organization';
    public const ADDITIONAL_PRODUCT_KIT = 'additional-product-kit';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadProductKitData::class
        ];
    }

    /**
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $organization = new Organization();
        $organization->setName(self::ADDITIONAL_ORGANIZATION);

        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        $additionalProductKit = $this->container->get('oro_product.service.duplicator')->duplicate($productKit);
        $additionalProductKit->setSku(self::ADDITIONAL_PRODUCT_KIT);
        $additionalProductKit->setOrganization($organization);

        $manager->persist($organization);
        $manager->flush();

        $this->setReference(self::ADDITIONAL_ORGANIZATION, $organization);
        $this->setReference(self::ADDITIONAL_PRODUCT_KIT, $additionalProductKit);
    }
}
