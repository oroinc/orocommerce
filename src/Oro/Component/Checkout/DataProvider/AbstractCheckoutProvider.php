<?php

namespace Oro\Component\Checkout\DataProvider;

abstract class AbstractCheckoutProvider implements CheckoutDataProviderInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * ${@inheritdoc}
     */
    public function getData($entity)
    {
        $cacheKey = $this->getCacheKey($entity);
        if (!isset($this->data[$cacheKey])) {
            $this->data[$cacheKey] = $this->prepareData($entity);
        }

        return $this->data[$cacheKey];
    }


    /**
     * @param object|array $entity
     * @return string
     */
    protected function getCacheKey($entity)
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

        return $cacheKey;
    }

    /**
     * @param object|array $entity
     * @return array
     */
    abstract protected function prepareData($entity);
}
