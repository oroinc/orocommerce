<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Entity;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryTest extends WebTestCase
{
    private ObjectManager $objectManager;

    private SlugRepository $slugRepository;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadCategoryData::class
        ]);

        $this->objectManager = self::getContainer()->get('doctrine')->getManager();

        $this->slugRepository =  $this->objectManager->getRepository(Slug::class);
    }

    public function testThatChildCategoriesDeletedWithSlugs()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $childrenSlugs = [];

        foreach ($category->getChildCategories() as $childCategory) {
            foreach ($childCategory->getSlugs() as $slug) {
                $childrenSlugs[] = $slug->getId();
            }
        }

        self::assertNotEmpty($childrenSlugs);

        $this->objectManager->remove($category);
        $this->objectManager->flush();

        foreach ($childrenSlugs as $slugID) {
            $this->assertNull($this->slugRepository->find($slugID));
        }
    }
}
