<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityGridListener
{
    const ACCOUNT_CATEGORY_VISIBILITY_GRID = 'account-category-visibility-grid';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_GRID = 'account-group-category-visibility-grid';
    const ACCOUNT_CATEGORY_VISIBILITY_ALIAS = 'accountCategoryVisibility.visibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS = 'accountGroupCategoryVisibility.visibility';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

    /**
     * @var string
     */
    protected $categoryClass;

    /**
     * @param ManagerRegistry $registry
     * @param VisibilityChoicesProvider $visibilityChoicesProvider
     */
    public function __construct(ManagerRegistry $registry, VisibilityChoicesProvider $visibilityChoicesProvider)
    {
        $this->registry = $registry;
        $this->visibilityChoicesProvider = $visibilityChoicesProvider;
    }

    /**
     * @param string $categoryClass
     */
    public function setCategoryClassName($categoryClass)
    {
        $this->categoryClass = $categoryClass;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $category = $this->getCategory($event->getParameters()->get('category_id'));
        if (null === $category) {
            return;
        }
        $config = $event->getConfig();
        $this->setVisibilityChoices($category, $config, '[columns][visibility]');
        $this->setVisibilityChoices($category, $config, '[filters][columns][visibility][options][field_options]');
    }

    /**
     * @param Category $category
     * @param DatagridConfiguration $config
     * @param string $path
     */
    protected function setVisibilityChoices($category, DatagridConfiguration $config, $path)
    {
        $pathConfig = $config->offsetGetByPath($path);

        $pathConfig['choices'] = $this->visibilityChoicesProvider
            ->getFormattedChoices($pathConfig['choicesSource'], $category);
        unset($pathConfig['choicesSource']);

        $config->offsetSetByPath($path, $pathConfig);
    }

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        if (!$this->isFilteredByDefaultValue($event->getDatagrid()->getParameters())) {
            return;
        }

        /** @var OrmDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getDatasource();
        /** @var array $parts */
        $parts = $dataSource->getQueryBuilder()->getDQLPart('where')->getParts();
        $regexp = implode('|', $this->getFieldAliases());
        foreach ($parts as $id => $part) {
            if (preg_match(sprintf('/%s/', $regexp), $part)) {
                unset($parts[$id]);
            }
        }
        $parts[] = $dataSource->getQueryBuilder()->expr()->isNull(
            $this->getFieldAlias($event->getDatagrid()->getName())
        );
        $dataSource->getQueryBuilder()->orWhere(
            call_user_func_array([
                $dataSource->getQueryBuilder()->expr(),
                'andX'
            ], $parts)
        );
        $event->getQuery()->setDQL($dataSource->getQueryBuilder()->getQuery()->getDQL());
    }

    /**
     * @param ParameterBag $params
     *
     * @return bool
     */
    protected function isFilteredByDefaultValue(ParameterBag $params)
    {
        if (!$params->get('_filter')) {
            return false;
        }

        foreach ($params->get('_filter')['visibility']['value'] as $value) {
            if ($this->isDefaultValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    protected function isDefaultValue($value)
    {
        return in_array(
            $value,
            [
                AccountCategoryVisibility::PARENT_CATEGORY,
                AccountGroupCategoryVisibility::PARENT_CATEGORY
            ],
            true
        );
    }

    /**
     * @param string $gridName
     *
     * @return string
     */
    protected function getFieldAlias($gridName)
    {
        if ($gridName === self::ACCOUNT_CATEGORY_VISIBILITY_GRID) {
            $alias = self::ACCOUNT_CATEGORY_VISIBILITY_ALIAS;
        } else {
            $alias = self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS;
        }

        return $alias;
    }

    /**
     * @return array
     */
    protected function getFieldAliases()
    {
        return [
            self::ACCOUNT_CATEGORY_VISIBILITY_ALIAS,
            self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS
        ];
    }

    /**
     * @param integer|null $categoryId
     * @return Category|null
     */
    protected function getCategory($categoryId)
    {
        $category = null;
        if ($categoryId) {
            $category = $this->registry->getRepository($this->categoryClass)->find($categoryId);
        }
        return $category;
    }
}
