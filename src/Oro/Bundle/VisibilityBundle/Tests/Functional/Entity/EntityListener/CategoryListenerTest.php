<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topics;

class CategoryListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var EntityManagerInterface */
    private $categoryManager;

    protected function setUp(): void
    {
        $this->initClient();
        self::enableMessageBuffering();

        $this->categoryManager = self::getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');
    }

    /**
     * @return Category
     */
    private function getCategory()
    {
        $category = new Category();
        $category->addTitle((new CategoryTitle())->setString('default title'));
        return $category;
    }

    public function testPreUpdateParentCategoryChange()
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
            Topics::CATEGORY_POSITION_CHANGE,
            ['id' => $newCategory->getId()]
        );
    }

    public function testPreRemove()
    {
        $newCategory = $this->getCategory();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->flush();

        self::clearMessageCollector();

        $id = $newCategory->getId();
        $this->categoryManager->remove($newCategory);
        $this->categoryManager->flush();

        self::assertMessageSent(
            Topics::CATEGORY_REMOVE,
            ['id' => $id]
        );
    }
}
