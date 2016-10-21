<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CategoryListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * @var EntityManager
     */
    protected $categoryManager;

    /*r
     * @var CategoryRepository
     */
    protected $categoryRepository;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->categoryManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Category::class);
        $this->categoryRepository = $this->categoryManager
            ->getRepository(Category::class);

        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();
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

        self::assertEmptyMessages('oro_customer.visibility.change_product_category');
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

        $this->assertMessageSent('oro_customer.visibility.change_product_category', ['id' => $product->getId()]);
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        $category->removeProduct($product);
        $this->categoryManager->flush();
        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();

        $this->assertMessageSent('oro_customer.visibility.change_product_category', ['id' => $product->getId()]);

        $category->addProduct($product);
        $this->categoryManager->flush();

        $this->getContainer()->get('oro_product.model.product_message_handler')->sendScheduledMessages();

        $this->assertMessageSent('oro_customer.visibility.change_product_category', ['id' => $product->getId()]);
    }
}
