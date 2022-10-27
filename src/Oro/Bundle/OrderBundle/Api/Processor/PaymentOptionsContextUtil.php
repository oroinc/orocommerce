<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * Provides a set of static methods to work with order payment options stored in the context.
 */
class PaymentOptionsContextUtil
{
    public const PAYMENT_METHOD = 'paymentMethod';

    private const PAYMENT_OPTIONS = 'order_payment_options';

    /**
     * Gets all payment options.
     */
    public static function all(ParameterBagInterface $sharedData, Order $order): ?ActionData
    {
        $orderHash = spl_object_hash($order);
        $paymentOptions = $sharedData->get(self::PAYMENT_OPTIONS);
        if (!$paymentOptions || !isset($paymentOptions[$orderHash])) {
            return null;
        }

        return $paymentOptions[$orderHash];
    }

    /**
     * Checks if the given payment option is set.
     */
    public static function has(ParameterBagInterface $sharedData, Order $order, string $name): bool
    {
        $paymentOptions = self::all($sharedData, $order);

        return null !== $paymentOptions && $paymentOptions->has($name);
    }

    /**
     * Gets a value for the given payment option.
     *
     * @param ParameterBagInterface $sharedData
     * @param Order                 $order
     * @param string                $name
     *
     * @return mixed
     */
    public static function get(ParameterBagInterface $sharedData, Order $order, string $name)
    {
        $paymentOptions = self::all($sharedData, $order);

        return null !== $paymentOptions && $paymentOptions->has($name)
            ? $paymentOptions->get($name)
            : null;
    }

    /**
     * Sets a value for the given payment option.
     * All set payment options will be passes to the action group specified in
     * {@see \Oro\Bundle\OrderBundle\Api\Processor\PlaceOrder::$orderPurchaseActionGroupName}.
     *
     * @param ParameterBagInterface $sharedData
     * @param Order                 $order
     * @param string                $name
     * @param mixed                 $value
     */
    public static function set(ParameterBagInterface $sharedData, Order $order, string $name, $value): void
    {
        $paymentOptions = self::all($sharedData, $order);
        if (null === $paymentOptions) {
            $paymentOptions = new ActionData(['order' => $order]);
            $allPaymentOptions = $sharedData->get(self::PAYMENT_OPTIONS) ?? [];
            $allPaymentOptions[spl_object_hash($order)] = $paymentOptions;
            $sharedData->set(self::PAYMENT_OPTIONS, $allPaymentOptions);
        }

        $paymentOptions->set($name, $value);
    }
}
