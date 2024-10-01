<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;

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
        if (Category::class === $entityClass) {
            $category = new Category();
            $category->setOrganization($repository->getReference('organization'));
            $category->addTitle($this->createCategoryTitle($em, 'Test Category'));
            $category->addTitle($this->createCategoryTitle(
                $em,
                'Test Category (de_DE)',
                $repository->getReference('de_DE')
            ));
            $category->addTitle($this->createCategoryTitle(
                $em,
                'Test Category (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $repository->setReference('category', $category);
            $em->persist($category);
            $em->flush();

            return ['category'];
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
        if (Category::class === $entityClass) {
            return 'Localization de_DE' === $locale
                ? 'Test Category (de_DE)'
                : 'Test Category';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createCategoryTitle(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): CategoryTitle {
        $title = new CategoryTitle();
        $title->setString($value);
        if (null !== $localization) {
            $title->setLocalization($localization);
        }
        $em->persist($title);

        return $title;
    }
}
