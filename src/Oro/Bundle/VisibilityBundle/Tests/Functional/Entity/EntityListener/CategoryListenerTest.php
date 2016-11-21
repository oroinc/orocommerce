<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\CatalogBundle\Entity\Category;
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
        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
        $this->messageProducer->clear();
        $this->messageProducer->enable();
    }

    public function testPreUpdateParentCategoryChange()
    {
        $newCategory = new Category();
        $parentCategory = new Category();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->persist($parentCategory);
        $this->categoryManager->flush();

        $newCategory->setParentCategory($parentCategory);
        $this->categoryManager->flush();
        $this->messageProducer->clear();

        $this->getContainer()->get('oro_catalog.model.category_message_handler')->sendScheduledMessages();
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
        $newCategory = new Category();
        $this->categoryManager->persist($newCategory);
        $this->categoryManager->flush();

        $id = $newCategory->getId();
        $this->categoryManager->remove($newCategory);
        $this->categoryManager->flush();
        $this->messageProducer->clear();

        $this->getContainer()->get('oro_catalog.model.category_message_handler')->sendScheduledMessages();
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
