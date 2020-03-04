<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Model\CategoryMessageHandler;
use Oro\Bundle\VisibilityBundle\Tests\Functional\MessageQueueTrait;

class CategoryListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * @var EntityManager
     */
    protected $categoryManager;

    protected function setUp()
    {
        $this->initClient();

        $this->categoryManager = self::getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');

        $this->cleanScheduledMessages();
    }

    /**
     * @return CategoryMessageHandler
     */
    protected function getMessageHandler()
    {
        return self::getContainer()->get('oro_visibility.model.category_message_handler');
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

        $this->cleanScheduledMessages();

        $newCategory->setParentCategory($parentCategory);
        $this->categoryManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.category_position_change',
            ['id' => $newCategory->getId()]
        );
    }

    public function testPreRemove()
    {
        $newCategory = $this->getCategory();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->flush();

        $this->cleanScheduledMessages();

        $id = $newCategory->getId();
        $this->categoryManager->remove($newCategory);
        $this->categoryManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.category_remove',
            ['id' => $id]
        );
    }
}
