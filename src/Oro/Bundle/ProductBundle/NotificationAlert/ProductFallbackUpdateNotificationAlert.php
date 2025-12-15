<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\NotificationAlert;

use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertInterface;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Notification alert item that instructs administrators to run the
 * fallback update command after a platform upgrade.
 */
class ProductFallbackUpdateNotificationAlert implements NotificationAlertInterface
{
    public const SOURCE_TYPE = 'Product';
    public const RESOURCE_TYPE = 'product_fallback_update';
    public const ALERT_TYPE_COMMAND_REQUIRED = 'fallback_required';
    public const COMMAND_NAME = 'oro:platform:post-upgrade-tasks --task=product_fallback';

    private string $id;
    private string $alertType;
    private string $message;
    private ?int $organizationId = null;

    public static function createForCommandReminder(
        string $message,
        ?int $organizationId = null
    ): self {
        $item = new self();
        $item->id = UUIDGenerator::v4();
        $item->alertType = self::ALERT_TYPE_COMMAND_REQUIRED;
        $item->message = $message;
        $item->organizationId = $organizationId;

        return $item;
    }

    public static function buildDefaultMessage(): string
    {
        return sprintf(
            'Run the "%s" command after the upgrade to backfill product fallback data asynchronously.',
            self::COMMAND_NAME
        );
    }

    #[\Override]
    public function getId(): string
    {
        return $this->id;
    }

    #[\Override]
    public function getSourceType(): string
    {
        return self::SOURCE_TYPE;
    }

    #[\Override]
    public function toArray(): array
    {
        $data = [
            NotificationAlertManager::ID => $this->id,
            NotificationAlertManager::SOURCE_TYPE  => self::SOURCE_TYPE,
            NotificationAlertManager::RESOURCE_TYPE => self::RESOURCE_TYPE,
            NotificationAlertManager::ALERT_TYPE => $this->alertType,
            NotificationAlertManager::MESSAGE => $this->message,
        ];

        if (null !== $this->organizationId) {
            $data[NotificationAlertManager::ORGANIZATION] = $this->organizationId;
        }

        return $data;
    }
}
