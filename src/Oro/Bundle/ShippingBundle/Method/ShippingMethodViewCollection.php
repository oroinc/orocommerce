<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * The collection of shipping method views.
 */
class ShippingMethodViewCollection
{
    private const TYPES_FIELD = 'types';

    private array $methodViews = [];
    private array $methodTypesViews = [];

    public function addMethodView(string $methodId, array $methodView): self
    {
        if ($this->hasMethodView($methodId)) {
            return $this;
        }

        $this->methodViews[$methodId] = $methodView;
        $this->methodTypesViews[$methodId] = [];

        return $this;
    }

    public function hasMethodView(string $methodId): bool
    {
        return \array_key_exists($methodId, $this->methodViews);
    }

    public function removeMethodView(string $methodId): self
    {
        if (false === $this->hasMethodView($methodId)) {
            return $this;
        }

        unset($this->methodViews[$methodId], $this->methodTypesViews[$methodId]);

        return $this;
    }

    public function getMethodView(string $methodId): ?array
    {
        if (false === $this->hasMethodView($methodId)) {
            return null;
        }

        return $this->methodViews[$methodId];
    }

    public function addMethodTypeView(string $methodId, string $methodTypeId, array $methodTypeView): self
    {
        if (false === $this->hasMethodView($methodId)) {
            return $this;
        }

        if ($this->hasMethodTypeView($methodId, $methodTypeId)) {
            return $this;
        }

        $this->methodTypesViews[$methodId][$methodTypeId] = $methodTypeView;

        return $this;
    }

    /**
     * @param string $methodId
     * @param array $methodTypesViews ['typeId' => $typeView, 'anotherTypeId' => $anotherTypeView, etc...]
     *
     * @return $this
     */
    public function addMethodTypesViews(string $methodId, array $methodTypesViews): self
    {
        foreach ($methodTypesViews as $typeId => $typeView) {
            $this->addMethodTypeView($methodId, $typeId, $typeView);
        }

        return $this;
    }

    public function hasMethodTypeView(string $methodId, string $methodTypeId): bool
    {
        if (false === $this->hasMethodView($methodId)) {
            return false;
        }

        return \array_key_exists($methodTypeId, $this->methodTypesViews[$methodId]);
    }

    public function getMethodTypeView(string $methodId, string $methodTypeId): ?array
    {
        if (false === $this->hasMethodTypeView($methodId, $methodTypeId)) {
            return null;
        }

        return $this->methodTypesViews[$methodId][$methodTypeId];
    }

    public function removeMethodTypeView(string $methodId, string $methodTypeId): self
    {
        if (false === $this->hasMethodTypeView($methodId, $methodTypeId)) {
            return $this;
        }

        unset($this->methodTypesViews[$methodId][$methodTypeId]);

        return $this;
    }

    public function getAllMethodsViews(): array
    {
        return $this->methodViews;
    }

    public function getAllMethodsTypesViews(): array
    {
        return $this->methodTypesViews;
    }

    public function toArray(): array
    {
        $resultingFullMethodViews = [];

        foreach ($this->methodViews as $methodId => $methodView) {
            if (empty($this->methodTypesViews[$methodId])) {
                continue;
            }

            if (!\array_key_exists($methodId, $resultingFullMethodViews)) {
                $resultingFullMethodViews[$methodId] = $methodView;
            }

            $resultingFullMethodViews[$methodId][self::TYPES_FIELD] = $this->methodTypesViews[$methodId];
        }

        uasort(
            $resultingFullMethodViews,
            function ($methodData1, $methodData2) {
                if (!\array_key_exists('sortOrder', $methodData1) || !\array_key_exists('sortOrder', $methodData2)) {
                    throw new \RuntimeException('Method View should contain sortOrder');
                }

                return $methodData1['sortOrder'] - $methodData2['sortOrder'];
            }
        );

        return $resultingFullMethodViews;
    }

    public function clear(): self
    {
        $this->methodViews = [];
        $this->methodTypesViews = [];

        return $this;
    }

    /**
     * Checks whether the collection has at least one method with types.
     */
    public function isEmpty(): bool
    {
        if (!$this->methodViews) {
            return true;
        }

        foreach ($this->methodViews as $methodId => $methodView) {
            if (!empty($this->methodTypesViews[$methodId])) {
                return false;
            }
        }

        return true;
    }
}
