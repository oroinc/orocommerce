<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCategoryPositionTopic;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnRemoveCategoryTopic;

class CategoryListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityManagerInterface $categoryManager;

    protected function setUp(): void
    {
        $this->initClient();
        self::enableMessageBuffering();

        $this->categoryManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(Category::class);
    }

    private function getCategory(): Category
    {
        $category = new Category();
        $category->addTitle((new CategoryTitle())->setString('default title'));

        return $category;
    }

    public function testPreUpdateParentCategoryChange(): void
    {
        $newCategory = $this->getCategory();
        $parentCategory = $this->getCategory();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->persist($parentCategory);
        $this->categoryManager->flush();

        self::clearMessageCollector();

        $newCategory->setParentCategory($parentCategory);
        $this->categoryManager->flush();

        self::assertMessageSent(
            VisibilityOnChangeCategoryPositionTopic::getName(),
            ['id' => $newCategory->getId()]
        );
    }

    public function testPreRemove(): void
    {
        $newCategory = $this->getCategory();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->flush();

        self::clearMessageCollector();

        $id = $newCategory->getId();
        $this->categoryManager->remove($newCategory);
        $this->categoryManager->flush();

        self::assertMessageSent(
            VisibilityOnRemoveCategoryTopic::getName(),
            ['id' => $id]
        );
    }
}
