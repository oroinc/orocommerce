<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CategoryListenerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $categoryManager;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var MessageCollector
     */
    protected $messageProducer;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->categoryManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Category::class);
        $this->categoryRepository = $this->categoryManager
            ->getRepository(Category::class);

        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->messageProducer = $this->getContainer()->get('oro_message_queue.client.message_producer');
        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
        $this->messageProducer->clear();
        $this->messageProducer->enable();
    }

    public function testCreateProduct()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $product = new Product();
        $product->setSku('TestSKU02');

        $em->persist($product);

        /** @var $category Category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $em->refresh($category);

        $category->addProduct($product);
        $em->flush();

        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
        $messages = $this->messageProducer->getSentMessages();
        $visibilityMessages = array_filter(
            $messages,
            function ($message) {
                return array_key_exists('topic', $message)
                    && $message['topic'] === 'oro_account.visibility.change_product_category';
            }
        );

        $this->assertEmpty($visibilityMessages);
    }

    public function testChangeProductCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $previousCategory = $this->categoryRepository->findOneByProduct($product);

        /** @var $newCategory Category */
        $newCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->categoryManager->refresh($newCategory);

        $previousCategory->removeProduct($product);
        $newCategory->addProduct($product);
        $this->categoryManager->flush();
        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
        $messages = $this->messageProducer->getSentMessages();
        $expectedMessages[] = [
            'topic' => 'oro_customer.visibility.change_product_category',
            'message' => ['id' => $product->getId()],
        ];
        $this->assertEquals(
            $expectedMessages,
            $messages
        );
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        $category->removeProduct($product);
        $this->categoryManager->flush();
        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
        $messages = $this->messageProducer->getSentMessages();
        $expectedMessages[] = [
            'topic' => 'oro_customer.visibility.change_product_category',
            'message' => ['id' => $product->getId()],
        ];
        $this->assertEquals(
            $expectedMessages,
            $messages
        );
        $category->addProduct($product);
        $this->categoryManager->flush();

        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
        $messages = $this->messageProducer->getSentMessages();
        $expectedMessages[] = [
            'topic' => 'oro_customer.visibility.change_product_category',
            'message' => ['id' => $product->getId()],
        ];
        $this->assertEquals(
            $expectedMessages,
            $messages
        );
    }
}
