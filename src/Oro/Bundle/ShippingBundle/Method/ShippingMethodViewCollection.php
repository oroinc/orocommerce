<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * Collection of shipping method views.
 */
class ShippingMethodViewCollection
{
    const TYPES_FIELD = 'types';

    /**
     * @var array
     */
    private $methodViews = [];

    /**
     * @var array
     */
    private $methodTypesViews = [];

    /**
     * @param string $methodId
     * @param array $methodView
     *
     * @return $this
     */
    public function addMethodView($methodId, array $methodView)
    {
        if ($this->hasMethodView($methodId)) {
            return $this;
        }

        $this->methodViews[$methodId] = $methodView;
        $this->methodTypesViews[$methodId] = [];

        return $this;
    }

    /**
     * @param string $methodId
     *
     * @return bool
     */
    public function hasMethodView($methodId)
    {
        return array_key_exists($methodId, $this->methodViews);
    }

    /**
     * @param string $methodId
     *
     * @return $this
     */
    public function removeMethodView($methodId)
    {
        if (false === $this->hasMethodView($methodId)) {
            return $this;
        }

        unset($this->methodViews[$methodId]);
        unset($this->methodTypesViews[$methodId]);

        return $this;
    }

    /**
     * @param string $methodId
     *
     * @return array|null
     */
    public function getMethodView($methodId)
    {
        if (false === $this->hasMethodView($methodId)) {
            return null;
        }

        return $this->methodViews[$methodId];
    }

    /**
     * @param string $methodId
     * @param string $methodTypeId
     * @param array $methodTypeView
     *
     * @return $this
     */
    public function addMethodTypeView($methodId, $methodTypeId, array $methodTypeView)
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
    public function addMethodTypesViews($methodId, array $methodTypesViews)
    {
        foreach ($methodTypesViews as $typeId => $typeView) {
            $this->addMethodTypeView($methodId, $typeId, $typeView);
        }

        return $this;
    }

    /**
     * @param string $methodId
     * @param string $methodTypeId
     *
     * @return bool
     */
    public function hasMethodTypeView($methodId, $methodTypeId)
    {
        if (false === $this->hasMethodView($methodId)) {
            return false;
        }

        return array_key_exists($methodTypeId, $this->methodTypesViews[$methodId]);
    }

    /**
     * @param string $methodId
     * @param string $methodTypeId
     *
     * @return array|null
     */
    public function getMethodTypeView($methodId, $methodTypeId)
    {
        if (false === $this->hasMethodTypeView($methodId, $methodTypeId)) {
            return null;
        }

        return $this->methodTypesViews[$methodId][$methodTypeId];
    }

    /**
     * @param string $methodId
     * @param string $methodTypeId
     *
     * @return $this
     */
    public function removeMethodTypeView($methodId, $methodTypeId)
    {
        if (false === $this->hasMethodTypeView($methodId, $methodTypeId)) {
            return $this;
        }

        unset($this->methodTypesViews[$methodId][$methodTypeId]);

        return $this;
    }

    /**
     * @return array
     */
    public function getAllMethodsViews()
    {
        return $this->methodViews;
    }

    /**
     * @return array
     */
    public function getAllMethodsTypesViews()
    {
        return $this->methodTypesViews;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $resultingFullMethodViews = [];

        foreach ($this->methodViews as $methodId => $methodView) {
            if (false === array_key_exists($methodId, $this->methodTypesViews)
                || [] === $this->methodTypesViews[$methodId]
            ) {
                continue;
            }

            if (false === array_key_exists($methodId, $resultingFullMethodViews)) {
                $resultingFullMethodViews[$methodId] = $methodView;
            }

            $resultingFullMethodViews[$methodId][self::TYPES_FIELD] = $this->methodTypesViews[$methodId];
        }

        uasort(
            $resultingFullMethodViews,
            function ($methodData1, $methodData2) {
                if (false === array_key_exists('sortOrder', $methodData1)
                    || false === array_key_exists('sortOrder', $methodData2)
                ) {
                    throw new \Exception('Method View should contain sortOrder');
                }

                return $methodData1['sortOrder'] - $methodData2['sortOrder'];
            }
        );

        return $resultingFullMethodViews;
    }

    /**
     * @return self
     */
    public function clear()
    {
        $this->methodViews = [];
        $this->methodTypesViews = [];

        return $this;
    }

    /**
     * If at least one method has types - collection is not empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (count($this->methodViews) === 0) {
            return true;
        }

        foreach ($this->methodViews as $methodId => $methodView) {
            if (false === array_key_exists($methodId, $this->methodTypesViews)
                || [] === $this->methodTypesViews[$methodId]
            ) {
                continue;
            }

            return false;
        }

        return true;
    }
}
