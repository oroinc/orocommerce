<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads demo brands.
 */
class LoadBrandDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use UserUtilityTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $slugGenerator = $this->container->get('oro_entity_config.slug.generator');
        $loadedBrands = [];
        foreach ($this->getBrands() as $row) {
            $name = new LocalizedFallbackValue();
            $name->setString($row['name']);

            $description = new LocalizedFallbackValue();
            $description->setWysiwyg($row['description']);

            $shortDescription = new LocalizedFallbackValue();
            $shortDescription->setText($row['short_description']);

            $brand = new Brand();
            $brand->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setStatus(Brand::STATUS_ENABLED)
                ->addName($name)
                ->addDescription($description)
                ->addShortDescription($shortDescription);

            $slugPrototype = new LocalizedFallbackValue();
            $slugPrototype->setString($slugGenerator->slugify($row['name']));
            $brand->addSlugPrototype($slugPrototype);

            $manager->persist($brand);
            $loadedBrands[] = $brand;
        }

        $manager->flush();

        $this->createSlugs($loadedBrands, $manager);
    }

    /**
     * @param array|Brand[] $brands
     * @param ObjectManager $manager
     */
    private function createSlugs(array $brands, ObjectManager $manager)
    {
        $slugRedirectGenerator = $this->container->get('oro_redirect.generator.slug_entity');

        foreach ($brands as $brand) {
            $slugRedirectGenerator->generate($brand, true);
        }

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCacheInterface) {
            $cache->flushAll();
        }
        $manager->flush();
    }

    private function getBrands(): \Iterator
    {
        $filePath = $this->getBrandsDataPath();

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            yield array_combine($headers, array_values($data));
        }

        fclose($handler);
    }

    protected function getBrandsDataPath(): string
    {
        $locator = $this->container->get('file_locator');

        return $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/brands.csv');
    }
}
