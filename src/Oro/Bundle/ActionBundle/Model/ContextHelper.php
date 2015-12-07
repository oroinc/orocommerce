<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var array */
    protected $actionContexts = [];

    /** @var  PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(DoctrineHelper $doctrineHelper, RequestStack $requestStack = null)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->normalizeContext(
            [
                'route' => $this->getRequestParameter('route'),
                'entityId' => $this->getRequestParameter('entityId'),
                'entityClass' => $this->getRequestParameter('entityClass'),
            ]
        );
    }

    /**
     * @param array|null $context
     * @return ActionContext
     */
    public function getActionContext(array $context = null)
    {
        if (!$context) {
            $context = $this->getContext();
        } else {
            $context = $this->normalizeContext($context);
        }

        $hash = $this->generateHash($context, ['entityClass', 'entityId']);

        if (!array_key_exists($hash, $this->actionContexts)) {
            $entity = null;

            if ($context['entityClass']) {
                $entity = $this->getEntityReference($context['entityClass'], $context['entityId']);
            }

            $this->actionContexts[$hash] = new ActionContext($entity ? ['data' => $entity] : []);
        }

        return $this->actionContexts[$hash];
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getRequestParameter($name, $default = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $request->get($name, $default) : $default;
    }

    /**
     * @param array $context
     * @return array
     */
    protected function normalizeContext(array $context)
    {
        return array_merge(
            [
                'route' => null,
                'entityId' => null,
                'entityClass' => null,
            ],
            $context
        );
    }

    /**
     * @param string $entityClass
     * @param mixed $entityId
     * @return Object
     */
    protected function getEntityReference($entityClass, $entityId)
    {
        $entity = null;

        if ($this->doctrineHelper->isManageableEntity($entityClass)) {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        }

        return $entity;
    }

    /**
     * @param array $context
     * @param array $properties
     * @return null|string
     */
    protected function generateHash(array $context, array $properties)
    {
        $string = null;
        foreach ($properties as $property) {
            $value = $this->getPropertyAccessor()->getValue($context, sprintf('[%s]', $property));

            $string .= '|' . (is_array($value) ? $this->arrayToString($value) : $value);
        }

        return $string ? md5($string) : null;
    }

    /**
     * @param array $array
     * @return string
     */
    protected function arrayToString(array $array)
    {
        $string = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->arrayToString($value);
            }

            $string .= $key . $value;
        }

        return $string;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
