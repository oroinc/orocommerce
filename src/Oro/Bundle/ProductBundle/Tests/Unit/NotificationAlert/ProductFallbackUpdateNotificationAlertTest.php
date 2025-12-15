<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\NotificationAlert;

use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlert;
use PHPUnit\Framework\TestCase;

final class ProductFallbackUpdateNotificationAlertTest extends TestCase
{
    public function testConstants(): void
    {
        self::assertSame('Product', ProductFallbackUpdateNotificationAlert::SOURCE_TYPE);
        self::assertSame('product_fallback_update', ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE);
        self::assertSame('fallback_required', ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED);
        self::assertSame(
            'oro:platform:post-upgrade-tasks --task=product_fallback',
            ProductFallbackUpdateNotificationAlert::COMMAND_NAME
        );
    }

    public function testCreateForCommandReminderWithoutOrganization(): void
    {
        $message = 'Test message';

        $alert = ProductFallbackUpdateNotificationAlert::createForCommandReminder($message);

        self::assertInstanceOf(ProductFallbackUpdateNotificationAlert::class, $alert);
        self::assertNotEmpty($alert->getId());
        self::assertSame(ProductFallbackUpdateNotificationAlert::SOURCE_TYPE, $alert->getSourceType());

        $array = $alert->toArray();
        self::assertSame($alert->getId(), $array[NotificationAlertManager::ID]);
        self::assertSame(
            ProductFallbackUpdateNotificationAlert::SOURCE_TYPE,
            $array[NotificationAlertManager::SOURCE_TYPE]
        );
        self::assertSame(
            ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE,
            $array[NotificationAlertManager::RESOURCE_TYPE]
        );
        self::assertSame(
            ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED,
            $array[NotificationAlertManager::ALERT_TYPE]
        );
        self::assertSame($message, $array[NotificationAlertManager::MESSAGE]);
        self::assertArrayNotHasKey(NotificationAlertManager::ORGANIZATION, $array);
    }

    public function testCreateForCommandReminderWithOrganization(): void
    {
        $message = 'Test message';
        $organizationId = 42;

        $alert = ProductFallbackUpdateNotificationAlert::createForCommandReminder($message, $organizationId);

        self::assertInstanceOf(ProductFallbackUpdateNotificationAlert::class, $alert);
        self::assertNotEmpty($alert->getId());

        $array = $alert->toArray();
        self::assertSame($alert->getId(), $array[NotificationAlertManager::ID]);
        self::assertSame(
            ProductFallbackUpdateNotificationAlert::SOURCE_TYPE,
            $array[NotificationAlertManager::SOURCE_TYPE]
        );
        self::assertSame(
            ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE,
            $array[NotificationAlertManager::RESOURCE_TYPE]
        );
        self::assertSame(
            ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED,
            $array[NotificationAlertManager::ALERT_TYPE]
        );
        self::assertSame($message, $array[NotificationAlertManager::MESSAGE]);
        self::assertArrayHasKey(NotificationAlertManager::ORGANIZATION, $array);
        self::assertSame($organizationId, $array[NotificationAlertManager::ORGANIZATION]);
    }

    public function testCreateForCommandReminderGeneratesUniqueIds(): void
    {
        $alert1 = ProductFallbackUpdateNotificationAlert::createForCommandReminder('Message 1');
        $alert2 = ProductFallbackUpdateNotificationAlert::createForCommandReminder('Message 2');

        self::assertNotSame($alert1->getId(), $alert2->getId());
    }

    public function testBuildDefaultMessage(): void
    {
        $message = ProductFallbackUpdateNotificationAlert::buildDefaultMessage();

        self::assertStringContainsString('oro:platform:post-upgrade-tasks --task=product_fallback', $message);
        self::assertStringContainsString('Run the', $message);
        self::assertStringContainsString('command after the upgrade', $message);
        self::assertStringContainsString('backfill product fallback data asynchronously', $message);
    }

    public function testGetId(): void
    {
        $alert = ProductFallbackUpdateNotificationAlert::createForCommandReminder('Test');

        $id = $alert->getId();

        self::assertNotEmpty($id);
        self::assertIsString($id);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id,
            'ID should be a valid UUID v4'
        );
    }

    public function testToArrayStructure(): void
    {
        $message = 'Custom message';
        $organizationId = 123;

        $alert = ProductFallbackUpdateNotificationAlert::createForCommandReminder($message, $organizationId);
        $array = $alert->toArray();

        self::assertIsArray($array);
        self::assertArrayHasKey(NotificationAlertManager::ID, $array);
        self::assertArrayHasKey(NotificationAlertManager::SOURCE_TYPE, $array);
        self::assertArrayHasKey(NotificationAlertManager::RESOURCE_TYPE, $array);
        self::assertArrayHasKey(NotificationAlertManager::ALERT_TYPE, $array);
        self::assertArrayHasKey(NotificationAlertManager::MESSAGE, $array);
        self::assertArrayHasKey(NotificationAlertManager::ORGANIZATION, $array);
    }

    public function testToArrayWithNullOrganizationExcludesOrganizationKey(): void
    {
        $alert = ProductFallbackUpdateNotificationAlert::createForCommandReminder('Test', null);
        $array = $alert->toArray();

        self::assertArrayNotHasKey(NotificationAlertManager::ORGANIZATION, $array);
    }

    public function testMultipleCallsToToArrayReturnConsistentData(): void
    {
        $alert = ProductFallbackUpdateNotificationAlert::createForCommandReminder('Test', 99);

        $array1 = $alert->toArray();
        $array2 = $alert->toArray();

        self::assertSame($array1, $array2);
    }
}
