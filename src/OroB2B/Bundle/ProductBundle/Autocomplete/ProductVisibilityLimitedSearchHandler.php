<?php

namespace OroB2B\Bundle\ProductBundle\Autocomplete;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  ProductRepository */
    protected $entityRepository;

    /**
     * @param string $entityName
     * @param array $properties
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        $entityName,
        array $properties,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($entityName, $properties);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryBuilder = $this->entityRepository->getSearchQueryBuilder($search, $firstResult, $maxResults);

        if ($request && $request->get('visibility_data')) {
            $event = new ProductSelectDBQueryEvent($queryBuilder, $request->get('visibility_data'));
            $this->eventDispatcher->dispatch(ProductSelectDBQueryEvent::NAME, $event);
        }

        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }
}
