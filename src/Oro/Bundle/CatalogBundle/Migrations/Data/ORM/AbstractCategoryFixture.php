<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads categories from predefined list
 */
abstract class AbstractCategoryFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Key is a category title, value is an array of categories
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Keeping the assoc of category => SKU of the image.
     *
     * @var array
     */
    protected $categoryImages = [];

    /**
     * Keeping the assoc of category => array of descriptions.
     *
     * @var array
     */
    protected $categoryDescriptions = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $category = $this->getCategory($manager);
        $this->addCategories($category, $this->categories, $manager);

        $manager->flush();

        $this->generateSlugs($this->categories, $this->container->get('oro_redirect.generator.slug_entity'));

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCacheInterface) {
            $cache->flushAll();
        }
        $manager->flush();
    }

    protected function addCategories(Category $root, array $categories, ObjectManager $manager): void
    {
        if (!$categories) {
            return;
        }

        $defaultOrganization = $root->getOrganization();

        foreach ($categories as $title => $nestedCategories) {
            $categoryTitle = new CategoryTitle();
            $categoryTitle->setString($title);

            $category = new Category();
            $category->addTitle($categoryTitle);
            $category->setOrganization($defaultOrganization);

            $slugString = $root->getParentCategory() ? $root->getSlugPrototype()->getString() . '/' . $title : $title;

            $slugPrototype = new LocalizedFallbackValue();
            $slugPrototype->setString(str_replace(' ', '-', strtolower($slugString)));
            $category->addSlugPrototype($slugPrototype);

            $this->fillCategoryDescriptions($manager, $category, $title);
            $this->fillCategoryImages($manager, $category, $title);

            $manager->persist($category);

            $this->addReference($title, $category);

            $root->addChildCategory($category);

            $this->addCategories($category, $nestedCategories, $manager);
        }
    }

    private function generateSlugs(array $categories, SlugEntityGenerator $slugEntityGenerator)
    {
        foreach ($categories as $title => $nestedCategories) {
            /** @var Category $category */
            $category = $this->getReference($title);

            $slugEntityGenerator->generate($category, true);

            $this->generateSlugs($nestedCategories, $slugEntityGenerator);
        }
    }

    private function fillCategoryDescriptions(ObjectManager $manager, Category $category, string $title): void
    {
        if (!empty($this->categoryDescriptions[$title])) {
            $descriptions = $this->categoryDescriptions[$title];

            if (isset($descriptions['short'])) {
                $shortDescription = new CategoryShortDescription();
                $shortDescription->setText($descriptions['short']);

                $manager->persist($shortDescription);

                $category->addShortDescription($shortDescription);
            }

            if (isset($descriptions['long'])) {
                $longDescription = new CategoryLongDescription();
                $longDescription->setWysiwyg($descriptions['long']);

                $manager->persist($longDescription);

                $category->addLongDescription($longDescription);
            }
        }
    }

    private function fillCategoryImages(ObjectManager $manager, Category $category, string $title): void
    {
        if (!empty($this->categoryImages[$title])) {
            $images = $this->categoryImages[$title];

            if (isset($images['small'])) {
                $category->setSmallImage($this->getCategoryImage($manager, $images['small']));
            }

            if (isset($images['large'])) {
                $category->setLargeImage($this->getCategoryImage($manager, $images['large']));
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param               $sku
     * @return null
     */
    protected function getCategoryImage(ObjectManager $manager, $sku)
    {
        $image   = null;
        $locator = $this->container->get('file_locator');

        try {
            $imagePath = $locator->locate($this->getImageName($sku));

            if (is_array($imagePath)) {
                $imagePath = current($imagePath);
            }

            $fileManager = $this->container->get('oro_attachment.file_manager');
            $image       = $fileManager->createFileEntity($imagePath);
            $manager->persist($image);
        } catch (\Exception $e) {
            //image not found
        }

        return $image;
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCategory(ObjectManager $manager): Category
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $categoryRepository = $manager->getRepository(Category::class);
        $queryBuilder = $categoryRepository->getMasterCatalogRootQueryBuilder();
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * Gets the file name to locate
     *
     * @param string $sku
     * @return string
     */
    protected function getImageName(string $sku): string
    {
        return sprintf('@OroCatalogBundle/Migrations/Data/Demo/ORM/images/%s.jpg', $sku);
    }
}
