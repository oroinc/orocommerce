<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CategoryListenerTest extends WebTestCase
{
    use MessageQueueExtension;

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

        $this->categoryManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');
        $this->categoryRepository = $this->categoryManager
            ->getRepository('OroCatalogBundle:Category');

        $this->loadFixtures(['Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);
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

        $this->assertMessageSent('oro_account.visibility.change_product_category', ['id' => $product->getId()]);
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        $category->removeProduct($product);
        $this->categoryManager->flush();
        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();

        $this->assertMessageSent('oro_account.visibility.change_product_category', ['id' => $product->getId()]);

        $category->addProduct($product);
        $this->categoryManager->flush();

        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();

        $this->assertMessageSent('oro_account.visibility.change_product_category', ['id' => $product->getId()]);
    }
}
