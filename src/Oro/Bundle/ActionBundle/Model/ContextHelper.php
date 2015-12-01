<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

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

        $entity = null;

        if ($context['entityClass']) {
            $entity = $this->getEntityReference($context['entityClass'], $context['entityId']);
        }

        return new ActionContext($entity ? ['data' => $entity] : []);
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
}
