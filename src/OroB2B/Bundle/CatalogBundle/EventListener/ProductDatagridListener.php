<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Event\PreBuild;

use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;
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
        if (!$category) {
            return;
        }

        $includeSubcategoriesChoice = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        $productCategoryIds = [$categoryId];
        if ($includeSubcategoriesChoice) {
            $productCategoryIds = array_merge($repo->getChildrenIds($category), $productCategoryIds);
        }
        $this->filterDatagridByCategoryIds($event, $productCategoryIds);
    }

    /**
     * @param PreBuild $event
     * @param array $productCategoryIds
     */
    protected function filterDatagridByCategoryIds(PreBuild $event, $productCategoryIds)
    {
        $config = $event->getConfig();

        $config->offsetSetByPath('[source][query][where][and]', ['productCategory.id IN (:productCategoryIds)']);
        $config->offsetSetByPath(
            DatasourceBindParametersListener::DATASOURCE_BIND_PARAMETERS_PATH,
            ['productCategoryIds']
        );
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
