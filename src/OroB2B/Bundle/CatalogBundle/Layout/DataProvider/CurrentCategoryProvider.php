<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class CurrentCategoryProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var RequestProductHandler
     */
    protected $requestProductHandler;

    /**
     * @param RequestProductHandler $requestProductHandler
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(RequestProductHandler $requestProductHandler, CategoryRepository $categoryRepository)
    {
        $this->requestProductHandler = $requestProductHandler;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        $categoryId = $this->requestProductHandler->getCategoryId();
        if ($categoryId) {
            return $this->categoryRepository->find($categoryId);
        }

        return $this->categoryRepository->getMasterCatalogRoot();
    }
}
