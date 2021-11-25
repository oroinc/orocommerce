<?php

namespace Oro\Bundle\PricingBundle\Async;

use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertInterface;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Represents a notification alert item for PriceList resource type.
 */
class PriceListCalculationNotificationAlert implements NotificationAlertInterface
{
    public const SOURCE_TYPE = 'Pricing';
    public const RESOURCE_TYPE = 'PriceList';
    public const ALERT_TYPE_SUCCESS = 'success';
    public const ALERT_TYPE_ERROR = 'error';
    public const ALERT_TYPE_INFO = 'info';
    public const ALERT_TYPE_WARNING = 'warning';
    public const OPERATION_ASSIGNED_PRODUCTS_BUILD = 'assigned_products_build';
    public const OPERATION_PRICE_RULES_BUILD = 'price_rules_build';

    protected string $id;
    protected string $alertType;
    protected ?string $operation = null;
    protected ?int $itemId = null;
    protected ?string $message = null;

    /**
     * This class cannot be instantiated via the constructor.
     */
    private function __construct()
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceType(): string
    {
        return self::SOURCE_TYPE;
    }

    public static function createForPriceRulesBuildError(
        int $itemId,
        ?string $message = null
    ): PriceListCalculationNotificationAlert {
        $item = new PriceListCalculationNotificationAlert();
        $item->id = UUIDGenerator::v4();
        $item->alertType = self::ALERT_TYPE_ERROR;
        $item->operation = self::OPERATION_PRICE_RULES_BUILD;
        $item->itemId = $itemId;
        $item->message = $message;

        return $item;
    }

    public static function createForAssignedProductsBuildError(
        int $itemId,
        ?string $message = null
    ): PriceListCalculationNotificationAlert {
        $item = new PriceListCalculationNotificationAlert();
        $item->id = UUIDGenerator::v4();
        $item->alertType = self::ALERT_TYPE_ERROR;
        $item->operation = self::OPERATION_ASSIGNED_PRODUCTS_BUILD;
        $item->itemId = $itemId;
        $item->message = $message;

        return $item;
    }

    public function toArray(): array
    {
        $data = [
            NotificationAlertManager::ID            => $this->id,
            NotificationAlertManager::SOURCE_TYPE   => self::SOURCE_TYPE,
            NotificationAlertManager::RESOURCE_TYPE => self::RESOURCE_TYPE,
            NotificationAlertManager::ALERT_TYPE    => $this->alertType
        ];
        if (null !== $this->operation) {
            $data[NotificationAlertManager::OPERATION] = $this->operation;
        }
        if (null !== $this->itemId) {
            $data[NotificationAlertManager::ITEM_ID] = $this->itemId;
        }
        if (null !== $this->message) {
            $data[NotificationAlertManager::MESSAGE] = $this->message;
        }

        return $data;
    }
}
