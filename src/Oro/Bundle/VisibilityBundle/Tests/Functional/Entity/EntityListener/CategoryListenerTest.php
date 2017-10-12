<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryListenerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $categoryManager;

    /**
     * @var MessageCollector
     */
    protected $messageProducer;

    protected function setUp()
    {
        $this->initClient();

        $this->categoryManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');

        $this->messageProducer = $this->getContainer()->get('oro_message_queue.client.message_producer');
        $this->getContainer()->get('oro_visibility.model.product_message_handler')->sendScheduledMessages();
        $this->messageProducer->clear();
    }

    /**
     * @return Category
     */
    private function getCategory()
    {
        $category = new Category();
        $category->addTitle((new LocalizedFallbackValue())->setString('default title'));
        return $category;
    }

    public function testPreUpdateParentCategoryChange()
    {
        $newCategory = $this->getCategory();
        $parentCategory = $this->getCategory();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->persist($parentCategory);
        $this->categoryManager->flush();

        $newCategory->setParentCategory($parentCategory);
        $this->categoryManager->flush();
        $this->messageProducer->clear();

        $this->getContainer()->get('oro_visibility.model.category_message_handler')->sendScheduledMessages();
        $messages = $this->messageProducer->getSentMessages();
        $expectedMessages = [
            [
                'topic' => 'oro_visibility.visibility.category_position_change',
                'message' => ['id' => $newCategory->getId()]
            ]
        ];
        $this->assertEquals($expectedMessages, $messages);
    }

    public function testPreRemove()
    {
        $newCategory = $this->getCategory();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->flush();

        $id = $newCategory->getId();
        $this->categoryManager->remove($newCategory);
        $this->categoryManager->flush();
        $this->messageProducer->clear();

        $this->getContainer()->get('oro_visibility.model.category_message_handler')->sendScheduledMessages();
        $messages = $this->messageProducer->getSentMessages();
        $expectedMessages = [
            [
                'topic' => 'oro_visibility.visibility.category_remove',
                'message' => ['id' => $id]
            ]
        ];
        $this->assertEquals($expectedMessages, $messages);
    }
}
