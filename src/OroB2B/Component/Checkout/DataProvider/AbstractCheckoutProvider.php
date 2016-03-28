<?php

namespace OroB2B\Component\Checkout\DataProvider;

abstract class AbstractCheckoutProvider implements CheckoutDataProviderInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * ${@inheritdoc}
     */
    public function getData($entity, $additionalData = [])
    {
        $cacheKey = $this->getCacheKey($entity, $additionalData);
        if (!isset($this->data[$cacheKey])) {
            $this->data[$cacheKey] = $this->prepareData($entity, $additionalData);
        }

        return $this->data[$cacheKey];
    }


    /**
     * @param object|array $entity
     * @param $additionalData
     * @return string
     */
    protected function getCacheKey($entity, $additionalData)
    {
        $cacheKey = '';
        if (is_object($entity)) {
            if (method_exists($entity, 'getId') && $entity->getId() !== null) {
                $cacheKey .= $entity->getId();
            } else {
                $cacheKey .= spl_object_hash($entity);
            }
        } else {
            $cacheKey .= md5(serialize($entity));
        }

        return $cacheKey . "_" . md5(serialize($additionalData));
    }

    /**
     * @param object|array $entity
     * @param array $additionalData
     * @return array
     */
    abstract protected function prepareData($entity, $additionalData);
}
