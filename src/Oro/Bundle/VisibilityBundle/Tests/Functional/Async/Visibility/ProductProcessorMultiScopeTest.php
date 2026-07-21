<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Async\Visibility;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadMultiScopeProductVisibilityData;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ProductProcessorMultiScopeTest extends WebTestCase
{
    use MessageQueueExtension;

    private ScopeManager $scopeManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadCategoryVisibilityData::class,
            LoadMultiScopeProductVisibilityData::class,
        ]);

        $this->getOptionalListenerManager()->enableListener('oro_visibility.event_listener.category_listener');

        $this->scopeManager = self::getContainer()->get('oro_scope.scope_manager');

        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();
    }

    public function testProcessWithMultipleScopesAndStaticVisibilityInOneScope(): void
    {
        $scopes = $this->getProductVisibilityScopes();
        /** @var Product $product */
        $product = $this->getReference(LoadMultiScopeProductVisibilityData::PRODUCT_WITH_STATIC_VISIBILITY);
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $staticScope = self::getContainer()
            ->get('oro_visibility.provider.visibility_scope_provider')
            ->getProductVisibilityScope($this->getReference(LoadWebsiteData::WEBSITE1));

        self::purgeMessageQueue();
        self::clearMessageCollector();

        $this->changeCategory($product, $category);

        self::assertMessageSent(
            VisibilityOnChangeProductCategoryTopic::getName(),
            ['id' => $product->getId()]
        );

        $processedMessages = $this->consumeMessages(
            1,
            VisibilityOnChangeProductCategoryTopic::getName()
        );

        self::assertCount(1, $processedMessages);
        self::assertEquals(MessageProcessorInterface::ACK, $processedMessages[0]['context']->getStatus());
        self::assertEquals(
            'oro_visibility.async.visibility.product_processor',
            $processedMessages[0]['context']->getMessageProcessorName()
        );

        $repository = self::getContainer()->get('doctrine')->getRepository(ProductVisibilityResolved::class);
        $resolvedVisibilities = $repository->findBy(['product' => $product], ['scope' => 'ASC']);
        self::assertCount(count($scopes), $resolvedVisibilities);

        $staticResolvedCount = 0;
        $categoryResolvedCount = 0;
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            if ($resolvedVisibility->getScope()->getId() === $staticScope->getId()) {
                self::assertEquals(BaseProductVisibilityResolved::SOURCE_STATIC, $resolvedVisibility->getSource());
                self::assertEquals(
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $resolvedVisibility->getVisibility()
                );
                $staticResolvedCount++;
                continue;
            }

            self::assertEquals(BaseProductVisibilityResolved::SOURCE_CATEGORY, $resolvedVisibility->getSource());
            $categoryResolvedCount++;
        }

        self::assertSame(1, $staticResolvedCount);
        self::assertSame(count($scopes) - 1, $categoryResolvedCount);
    }

    /**
     * @return Scope[]
     */
    private function getProductVisibilityScopes(): array
    {
        $scopes = iterator_to_array($this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE));
        if (count($scopes) < 2) {
            self::markTestSkipped('At least two product visibility scopes are required.');
        }

        return $scopes;
    }

    private function changeCategory(Product $product, Category $newCategory): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Category::class);
        $categoryRepository = $entityManager->getRepository(Category::class);
        $previousCategory = $categoryRepository->findOneByProduct($product);

        $entityManager->refresh($newCategory);
        $previousCategory->removeProduct($product);
        $newCategory->addProduct($product);
        $entityManager->flush();
    }
}
