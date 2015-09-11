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
        $includeSubcategoriesChoice = $this->requestProductHandler->getIncludeSubcategoriesChoice();

        /** @var CategoryRepository $repo */
        $repo = $this->doctrine->getRepository($this->dataClass);
        /** @var Category $category */
        $category = $repo->find($categoryId);
        if (!$category) {
            return;
        }
        if ($includeSubcategoriesChoice) {
            $productCategoryIds = array_merge($repo->getChildrenIds($category), [$categoryId]);
        } else {
            $productCategoryIds = $categoryId;
        }
        $this->filterDatagridByCategoryIds($event, $productCategoryIds);
    }

    /**
     * @param PreBuild $event
     * @param array|int $productCategoryIds
     */
    protected function filterDatagridByCategoryIds(PreBuild $event, $productCategoryIds)
    {
        $config = $event->getConfig();
        if (is_array($productCategoryIds)) {
            $where = 'productCategory.id = :productCategoryIds';
        } else {
            $where = 'productCategory.id IN (:productCategoryIds)';
        }
        $config->offsetSetByPath('[source][query][where][and]', [$where]);
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
