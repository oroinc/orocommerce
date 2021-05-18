<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

class CategoryListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var EntityManagerInterface */
    private $categoryManager;

    /** @var CategoryRepository */
    private $categoryRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductVisibilityData::class]);
        self::enableMessageBuffering();

        $this->getOptionalListenerManager()->enableListener('oro_visibility.event_listener.category_listener');
        $this->getOptionalListenerManager()->enableListener('oro_visibility.entity_listener.change_product_category');

        $this->categoryManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(Category::class);
        $this->categoryRepository = $this->categoryManager
            ->getRepository(Category::class);
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

        self::assertMessageSent(
            Topics::CHANGE_PRODUCT_CATEGORY,
            ['id' => $product->getId()]
        );
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        $category->removeProduct($product);
        $this->categoryManager->flush();

        self::assertMessageSent(
            Topics::CHANGE_PRODUCT_CATEGORY,
            ['id' => $product->getId()]
        );

        $category->addProduct($product);
        $this->categoryManager->flush();

        self::assertMessageSent(
            Topics::CHANGE_PRODUCT_CATEGORY,
            ['id' => $product->getId()]
        );
    }
}
