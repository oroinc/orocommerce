<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Manager;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ProductManager
{
    /** @var  RegistryInterface */
    protected $registry;

    /** @var string */
    protected $dataClass;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack $requestStack
     * @param RegistryInterface $registry
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        RegistryInterface $registry
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param array $dataParameters
     * @param Request|null $request
     * @return QueryBuilder
     */
    public function createVisibleProductQueryBuilder(array $dataParameters, Request $request = null)
    {
        /** @var ProductRepository $repo */
        $repo = $this->registry->getManagerForClass($this->dataClass)->getRepository($this->dataClass);

        return $this->restrictQueryBuilderByProductVisibility(
            $repo->createQueryBuilder('product'),
            $dataParameters,
            $request
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $dataParameters
     * @param Request|null $request
     * @return QueryBuilder
     */
    public function restrictQueryBuilderByProductVisibility(
        QueryBuilder $queryBuilder,
        array $dataParameters,
        Request $request = null
    ) {
        if (!$request) {
            $request = $this->requestStack->getCurrentRequest();
        }
        $this->eventDispatcher->dispatch(
            ProductSelectDBQueryEvent::NAME,
            new ProductSelectDBQueryEvent($queryBuilder, new ParameterBag($dataParameters), $request)
        );

        return $queryBuilder;
    }
}
