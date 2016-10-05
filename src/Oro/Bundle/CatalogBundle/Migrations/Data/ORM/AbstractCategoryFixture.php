<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

abstract class AbstractCategoryFixture extends AbstractFixture implements ContainerAwareInterface
{
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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');
        $root               = $categoryRepository->getMasterCatalogRoot();

        $this->addCategories($root, $this->categories, $this->categoryImages, $manager);

        $manager->flush();
    }

    /**
     * @param Category      $root
     * @param array         $categories
     * @param array         $images
     * @param ObjectManager $manager
     */
    protected function addCategories(Category $root, array $categories, array $images, ObjectManager $manager)
    {
        if (!$categories) {
            return;
        }

        foreach ($categories as $title => $nestedCategories) {
            $categoryTitle = new LocalizedFallbackValue();
            $categoryTitle->setString($title);

            $category = new Category();
            $category->addTitle($categoryTitle);
            $category->setSmallImage($this->getCategoryImage($manager, $images[$title]));

            $manager->persist($category);

            $this->addReference($title, $category);

            $root->addChildCategory($category);

            $this->addCategories($category, $nestedCategories, $images, $manager);
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
            $imagePath = $locator->locate(sprintf('@OroProductBundle/Migrations/Data/Demo/ORM/images/%s.jpg', $sku));

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
}
