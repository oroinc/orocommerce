<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class DatagridListener
{
    const CATEGORY_COLUMN = 'category_name';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var RequestProductHandler */
    protected $requestProductHandler;

    /** @var string */
    protected $dataClass;

    /**
     * @param ManagerRegistry $doctrine
     * @param RequestProductHandler $requestProductHandler
     */
    public function __construct(ManagerRegistry $doctrine, RequestProductHandler $requestProductHandler)
    {
        $this->doctrine = $doctrine;
        $this->requestProductHandler = $requestProductHandler;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeProductsSelect(BuildBefore $event)
    {
        $this->addCategoryJoin($event->getConfig());
        $this->addCategoryRelation($event->getConfig());
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuildProducts(PreBuild $event)
    {
        $this->addFilterByCategory($event);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addCategoryRelation(DatagridConfiguration $config)
    {
        // select
        $categoryTitleSelect = 'categoryTitle.string as '.self::CATEGORY_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $categoryTitleSelect);

        $joinCategoryTitles = [
            'join' => 'productCategory.titles',
            'alias' => 'categoryTitle',
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $joinCategoryTitles);

        // conditions - where condition is required to prevent selection of extra joined rows
        $where = 'categoryTitle.locale IS NULL';
        $this->addConfigElement($config, '[source][query][where][and]', $where);

        // columns
        $categoryColumn = ['label' => 'orob2b.catalog.category.entity_label'];
        $this->addConfigElement($config, '[columns]', $categoryColumn, self::CATEGORY_COLUMN);

        // sorter
        $categorySorter = ['data_name' => self::CATEGORY_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $categorySorter, self::CATEGORY_COLUMN);

        // filter
        $categoryFilter = [
            'type' => 'string',
            'data_name' => 'categoryTitle.string'
        ];
        $this->addConfigElement($config, '[filters][columns]', $categoryFilter, self::CATEGORY_COLUMN);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addCategoryJoin(DatagridConfiguration $config)
    {
        $path = '[source][query][join][left]';
        // join
        $joinCategory = [
            'join' => 'OroB2BCatalogBundle:Category',
            'alias' => 'productCategory',
            'conditionType' => 'WITH',
            'condition' => 'product MEMBER OF productCategory.products'
        ];
        $joins = $config->offsetGetByPath($path);
        if (is_array($joins)) {
            foreach ($joins as $join) {
                if ($join === $joinCategory) {
                    return;
                }
            }
        }
        $this->addConfigElement($config, $path, $joinCategory);
    }

    /**
     * @param PreBuild $event
     */
    protected function addFilterByCategory(PreBuild $event)
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

        $config = $event->getConfig();
        $config->offsetSetByPath('[source][query][where][and]', ['productCategory.id IN (:productCategoryIds)']);
        $config->offsetSetByPath(
            DatasourceBindParametersListener::DATASOURCE_BIND_PARAMETERS_PATH,
            ['productCategoryIds']
        );
        $parameters = $event->getParameters();
        $parameters->set('productCategoryIds', $productCategoryIds);

        $this->addCategoryJoin($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }
}
