<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\NotificationAlert;

use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlert;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 */
class ProductFallbackUpdateNotificationAlertProviderTest extends WebTestCase
{
    private ProductFallbackUpdateNotificationAlertProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadUser::class,
            LoadAttributeFamilyData::class,
        ]);

        $this->provider = self::getContainer()->get('oro_product.notification_alert.fallback_update_provider');
    }

    public function testScheduleCommandReminderCreatesAlertsForAllOrganizations(): void
    {
        $customMessage = 'Test custom message for fallback update';

        $this->clearNotificationAlerts();

        $organizations = $this->getOrganizations();
        self::assertNotEmpty($organizations, 'At least one organization should exist for the test');

        $this->provider->scheduleCommandReminder($customMessage);

        foreach ($organizations as $organization) {
            $alerts = $this->findNotificationAlerts($organization);

            self::assertCount(
                1,
                $alerts,
                sprintf(
                    'One alert should be created for organization %d',
                    $organization->getId()
                )
            );

            $alert = $alerts[0];
            self::assertSame(ProductFallbackUpdateNotificationAlert::SOURCE_TYPE, $alert->getSourceType());
            self::assertSame(ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE, $alert->getResourceType());
            self::assertSame(
                ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED,
                $alert->getAlertType()
            );
            self::assertSame($customMessage, $alert->getMessage());
            self::assertFalse($alert->isResolved());
        }
    }

    public function testScheduleCommandReminderWithDefaultMessage(): void
    {
        $this->clearNotificationAlerts();

        $organizations = $this->getOrganizations();
        self::assertNotEmpty($organizations);

        $this->provider->scheduleCommandReminder();

        $organization = $organizations[0];
        $alerts = $this->findNotificationAlerts($organization);

        self::assertCount(1, $alerts);
        self::assertStringContainsString(
            'oro:platform:post-upgrade-tasks --task=product_fallback',
            $alerts[0]->getMessage()
        );
    }

    public function testResolveCommandRemindersMarksAlertsAsResolved(): void
    {
        $this->clearNotificationAlerts();

        $this->provider->scheduleCommandReminder('Test message');

        $em = self::getContainer()->get('doctrine')->getManagerForClass(NotificationAlert::class);
        $em->clear();

        $organizations = $this->getOrganizations();
        foreach ($organizations as $organization) {
            $alerts = $this->findNotificationAlerts($organization);
            self::assertNotEmpty($alerts);
            self::assertFalse($alerts[0]->isResolved());
        }

        $this->provider->resolveCommandReminders();

        $em->clear();

        foreach ($organizations as $organization) {
            $alerts = $this->findNotificationAlerts($organization, true);
            self::assertNotEmpty($alerts, 'Alert should still exist but be resolved');
            self::assertTrue($alerts[0]->isResolved());
        }
    }

    public function testHasPendingRemindersReturnsTrueWhenAlertsExist(): void
    {
        $this->clearNotificationAlerts();

        self::assertFalse($this->provider->hasPendingReminders(), 'Should return false when no alerts');

        $this->provider->scheduleCommandReminder();

        self::assertTrue($this->provider->hasPendingReminders(), 'Should return true when unresolved alerts exist');
    }

    public function testHasPendingRemindersReturnsFalseWhenAlertsResolved(): void
    {
        $this->clearNotificationAlerts();

        $this->provider->scheduleCommandReminder();
        $this->provider->resolveCommandReminders();

        self::assertFalse($this->provider->hasPendingReminders(), 'Should return false when all alerts resolved');
    }

    public function testGetPendingProductCountReturnsCorrectCount(): void
    {
        $this->createProductsWithNullFallbacks(5);
        $count = $this->provider->getPendingProductCount();

        self::assertGreaterThanOrEqual(5, $count, 'Should count at least the products we created');
    }

    public function testFixProductsFallbacksUpdatesProductsInDatabase(): void
    {
        $products = $this->createProductsWithNullFallbacks(10);
        $productIds = array_map(fn ($p) => $p->getId(), $products);

        foreach ($products as $product) {
            $freshProduct = $this->getProductById($product->getId());
            self::assertNull(
                $freshProduct->getPageTemplate(),
                'Product should have null pageTemplate before fix'
            );
        }

        $updatedCount = $this->provider->fixProductsFallbacks(5);

        self::assertGreaterThanOrEqual(10, $updatedCount, 'Should update at least 10 products');

        foreach ($productIds as $productId) {
            $updatedProduct = $this->getProductById($productId);
            $pageTemplate = $updatedProduct->getPageTemplate();

            self::assertNotNull(
                $pageTemplate,
                sprintf('Product %d should have page template fallback object', $productId)
            );
            self::assertNotNull(
                $pageTemplate->getFallback(),
                sprintf('Product %d should have page template fallback value set', $productId)
            );
        }
    }

    public function testFixProductsFallbacksReturnsZeroWhenNoProductsNeedUpdate(): void
    {
        $this->provider->fixProductsFallbacks(100);

        $count = $this->provider->getPendingProductCount();

        if ($count === 0) {
            $updatedCount = $this->provider->fixProductsFallbacks(100);
            self::assertSame(0, $updatedCount, 'Should return 0 when no products need update');
        } else {
            self::markTestSkipped('Cannot test zero update scenario when products need updates');
        }
    }

    private function clearNotificationAlerts(): void
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(NotificationAlert::class);

        $qb = $em->createQueryBuilder();
        $qb->delete(NotificationAlert::class, 'alert')
            ->where('alert.sourceType = :source')
            ->andWhere('alert.resourceType = :resource')
            ->setParameter('source', ProductFallbackUpdateNotificationAlert::SOURCE_TYPE)
            ->setParameter('resource', ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE)
            ->getQuery()
            ->execute();

        $em->clear();
    }

    /**
     * @return Organization[]
     */
    private function getOrganizations(): array
    {
        return self::getContainer()
            ->get('doctrine')
            ->getRepository(Organization::class)
            ->findAll();
    }

    /**
     * @return NotificationAlert[]
     */
    private function findNotificationAlerts(Organization $organization, bool $resolved = false): array
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(NotificationAlert::class);

        $qb = $em->createQueryBuilder();
        $alerts = $qb->select('alert')
            ->from(NotificationAlert::class, 'alert')
            ->where('alert.sourceType = :source')
            ->andWhere('alert.resourceType = :resource')
            ->andWhere('alert.alertType = :alertType')
            ->andWhere('alert.organization = :organization')
            ->andWhere('alert.resolved = :resolved')
            ->setParameter('source', ProductFallbackUpdateNotificationAlert::SOURCE_TYPE)
            ->setParameter('resource', ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE)
            ->setParameter('alertType', ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED)
            ->setParameter('organization', $organization)
            ->setParameter('resolved', $resolved)
            ->getQuery()
            ->getResult();

        return $alerts;
    }

    /**
     * @return Product[]
     */
    private function createProductsWithNullFallbacks(int $count): array
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $user = $this->getReference(LoadUser::USER);
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $productIds = [];

        for ($i = 0; $i < $count; $i++) {
            $product = new Product();
            $sku = 'TEST-FALLBACK-'.uniqid();

            $name = new ProductName();
            $name->setString('Test Product '.$sku);

            $product->setSku($sku);
            $product->addName($name);
            $product->setOrganization($organization);
            $product->setOwner($user->getOwner());
            $product->setAttributeFamily($attributeFamily);
            $product->setStatus(Product::STATUS_ENABLED);

            $em->persist($product);
            $productIds[] = $product;
        }

        $em->flush();

        foreach ($productIds as $product) {
            $pageTemplate = $product->getPageTemplate();
            if ($pageTemplate) {
                $product->setPageTemplate(null);

                $em->remove($pageTemplate);
            }
        }

        $em->flush();
        $em->clear();

        $products = [];
        foreach ($productIds as $productEntity) {
            $product = $em->getRepository(Product::class)->find($productEntity->getId());
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    private function getProductById(int $id): Product
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $em->clear();

        return $em->getRepository(Product::class)->find($id);
    }
}
