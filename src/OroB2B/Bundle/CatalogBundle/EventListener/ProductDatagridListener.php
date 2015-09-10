<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Event\PreBuild;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class ProductDatagridListener
{
    /** @var Registry */
    protected $doctrine;

    /** @var RequestProductHandler */
    protected $requestProductHandler;

    /** @var string */
    protected $dataClass;

    /**
     * @param Registry $doctrine
     * @param RequestProductHandler $requestProductHandler
     */
    public function __construct(Registry $doctrine, RequestProductHandler $requestProductHandler)
    {
        $this->doctrine = $doctrine;
        $this->requestProductHandler = $requestProductHandler;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $categoryId = $this->requestProductHandler->getCategoryId();
        if (!$categoryId) {
            return;
        }
        /** @var CategoryRepository $repo */
        $repo = $this->doctrine->getRepository($this->dataClass);
        /** @var Category $category */
        $category = $repo->find($categoryId);
        $productCategoryIds = array_merge($repo->getChildrenIds($category), [$categoryId]);
        $this->filterDatagridByCategoryIds($event, $productCategoryIds);
    }

    /**
     * @param PreBuild $event
     * @param array $productCategoryIds
     */
    protected function filterDatagridByCategoryIds(PreBuild $event, $productCategoryIds)
    {
        $config = $event->getConfig();
        $and = 'productCategory.id IN (:productCategoryIds)';
        $config->offsetSetByPath('[source][query][where][and]', [$and]);
        $config->offsetSetByPath('[source][bind_parameters]', ['productCategoryIds']);
        $parameters = $event->getParameters();
        $parameters->set('productCategoryIds', $productCategoryIds);
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }
}
