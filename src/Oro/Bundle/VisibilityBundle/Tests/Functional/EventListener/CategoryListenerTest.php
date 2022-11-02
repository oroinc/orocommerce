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
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

class CategoryListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityManagerInterface $categoryManager;

    private CategoryRepository $categoryRepository;

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

    public function testChangeProductCategory(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $previousCategory = $this->categoryRepository->findOneByProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->categoryManager->refresh($newCategory);

        $previousCategory->removeProduct($product);
        $newCategory->addProduct($product);
        $this->categoryManager->flush();

        self::assertMessageSent(
            VisibilityOnChangeProductCategoryTopic::getName(),
            ['id' => $product->getId()]
        );
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        $category->removeProduct($product);
        $this->categoryManager->flush();

        self::assertMessageSent(
            VisibilityOnChangeProductCategoryTopic::getName(),
            ['id' => $product->getId()]
        );

        $category->addProduct($product);
        $this->categoryManager->flush();

        self::assertMessageSent(
            VisibilityOnChangeProductCategoryTopic::getName(),
            ['id' => $product->getId()]
        );
    }
}
