<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;

use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;

class VisibilityGridListener
{
    const VISIBILITY_FIELD = 'visibilityAlias.visibility';

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
    protected $visibilityClass;

    /**
     * @param ManagerRegistry $registry
     * @param VisibilityChoicesProvider $visibilityChoicesProvider
     */
    public function __construct(ManagerRegistry $registry, VisibilityChoicesProvider $visibilityChoicesProvider)
    {
        $this->registry = $registry;
        $this->visibilityChoicesProvider = $visibilityChoicesProvider;
    }

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $this->visibilityClass = $config->offsetGetByPath('[options][visibilityTarget]');
        $entity = $this->getEntity($event->getParameters()->get('target_entity_id'));
        $this->setVisibilityChoices($entity, $config, '[columns][visibility]');
        $this->setVisibilityChoices($entity, $config, '[filters][columns][visibility][options][field_options]');
    }

    protected function setVisibilityChoices($entity, DatagridConfiguration $config, $path)
    {
        $pathConfig = $config->offsetGetByPath($path);
        $pathConfig['choices'] = $this->visibilityChoicesProvider->getFormattedChoices($this->visibilityClass, $entity);
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
        foreach ($parts as $id => $part) {
            if (preg_match(sprintf('/%s/', self::VISIBILITY_FIELD), $part)) {
                unset($parts[$id]);
            }
        }
        $parts[] = $dataSource->getQueryBuilder()->expr()->isNull(self::VISIBILITY_FIELD);
        $dataSource->getQueryBuilder()->orWhere(
            call_user_func_array(
                [
                    $dataSource->getQueryBuilder()->expr(),
                    'andX',
                ],
                $parts
            )
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
        /** @var string $defaultValue */
        $defaultValue = call_user_func([$this->visibilityClass, 'getDefault']);

        return $defaultValue === $value;
    }

    /**
     * @param integer|null $entityId
     * @return object|null
     */
    protected function getEntity($entityId)
    {
        $entity = null;
        if ($entityId) {
            $entity = $this->registry->getRepository('OroB2BCatalogBundle:Category')->find($entityId);
        }

        return $entity;
    }
}
