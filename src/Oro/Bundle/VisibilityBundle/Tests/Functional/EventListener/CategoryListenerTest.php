<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Bundle\VisibilityBundle\Tests\Functional\MessageQueueTrait;

class CategoryListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * @var EntityManager
     */
    protected $categoryManager;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->categoryManager = self::getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');
        $this->categoryRepository = $this->categoryManager
            ->getRepository('OroCatalogBundle:Category');

        $this->loadFixtures(['Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);

        $this->cleanScheduledMessages();
    }

    /**
     * @return VisibilityMessageHandler
     */
    protected function getMessageHandler(): VisibilityMessageHandler
    {
        return self::getContainer()->get('oro_visibility.visibility_message_handler');
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.change_product_category',
            [
                VisibilityMessageFactory::ID => $product->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => Product::class,
            ]
        );
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        $category->removeProduct($product);
        $this->categoryManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.change_product_category',
            [
                VisibilityMessageFactory::ID => $product->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => Product::class,
            ]
        );

        $category->addProduct($product);
        $this->categoryManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.change_product_category',
            [
                VisibilityMessageFactory::ID => $product->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => Product::class,
            ]
        );
    }
}
