<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Converter;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataReverseConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;

/**
 * Converts a value for "shippingMethod" attribute of order and order line item entities.
 */
class OrderShippingMethodConverter implements
    ComplexDataConverterInterface,
    ComplexDataReverseConverterInterface
{
    private const string SHIPPING_METHOD = 'shippingMethod';

    private ?array $resolvedShippingMethods = null;

    public function __construct(
        private readonly ShippingMethodProviderInterface $shippingMethodProvider,
        private readonly ShippingMethodLabelTranslator $shippingMethodLabelTranslator
    ) {
    }

    #[\Override]
    public function convert(array $item, mixed $sourceData): array
    {
        /** @var array $sourceData */

        if (!empty($sourceData[self::SHIPPING_METHOD]) && \is_string($sourceData[self::SHIPPING_METHOD])) {
            $resolvedShippingMethod = $this->resolveShippingMethod($sourceData[self::SHIPPING_METHOD]);
            if ($resolvedShippingMethod) {
                $item[self::ENTITY][JsonApiDoc::ATTRIBUTES]['shippingMethod'] = $resolvedShippingMethod[0];
                $item[self::ENTITY][JsonApiDoc::ATTRIBUTES]['shippingMethodType'] = $resolvedShippingMethod[1];
            }
        }

        return $item;
    }

    #[\Override]
    public function reverseConvert(array $item, object $sourceEntity): array
    {
        /** @var Order|OrderLineItem $sourceEntity */

        if ($sourceEntity->getShippingMethod()) {
            $item[self::SHIPPING_METHOD] = $this->getShippingMethodLabel(
                $sourceEntity->getShippingMethod(),
                $sourceEntity->getShippingMethodType()
            );
        }

        return $item;
    }

    private function resolveShippingMethod(string $label): ?array
    {
        if (null === $this->resolvedShippingMethods) {
            $this->resolvedShippingMethods = $this->loadResolvedShippingMethods();
        }

        return $this->resolvedShippingMethods[$label] ?? null;
    }

    private function loadResolvedShippingMethods(): array
    {
        $resolvedShippingMethods = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            foreach ($shippingMethod->getTypes() as $shippingMethodType) {
                $shippingMethodLabel = $this->getShippingMethodLabel(
                    $shippingMethod->getIdentifier(),
                    $shippingMethodType->getIdentifier()
                );
                $resolvedShippingMethods[$shippingMethodLabel] = [
                    $shippingMethod->getIdentifier(),
                    $shippingMethodType->getIdentifier()
                ];
            }
        }

        return $resolvedShippingMethods;
    }

    private function getShippingMethodLabel(?string $shippingMethod, ?string $shippingMethodType): string
    {
        return $this->shippingMethodLabelTranslator->getShippingMethodWithTypeLabel(
            $shippingMethod,
            $shippingMethodType
        );
    }
}
