<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

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
     * @var array
     */
    protected $subscribedGridConfig;


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
     * @param string $datagrid
     * @param string $visibilityEntityClass
     * @param string $targetEntityClass
     */
    public function addSubscribedGridConfig($datagrid, $visibilityEntityClass, $targetEntityClass)
    {
        $this->subscribedGridConfig[$datagrid] =
            [
                'visibilityEntityClass' => $visibilityEntityClass,
                'targetEntityClass' => $targetEntityClass,
            ];
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $datagridName = $config->getName();
        $visibilityClass = $this->subscribedGridConfig[$datagridName]['visibilityEntityClass'];
        $params = $event->getParameters();
        $targetEntity = $this->getEntity(
            $params->get('target_entity_id'),
            $this->subscribedGridConfig[$datagridName]['targetEntityClass']
        );
        if (is_a($visibilityClass, 'Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface', true)) {
            $selectorPath = '[options][cellSelection][selector]';
            $scopePath = '[scope]';
            $websiteId = $params->get('website_id');
            $config->offsetSetByPath(
                $selectorPath,
                sprintf(
                    '%s-%d',
                    $config->offsetGetByPath($selectorPath),
                    $websiteId
                )
            );
            $config->offsetSetByPath(
                $scopePath,
                sprintf(
                    '%s-%d',
                    $config->offsetGetByPath($scopePath),
                    $websiteId
                )
            );
        }
        $this->setVisibilityChoices(
            $targetEntity,
            $config,
            '[columns][visibility]',
            $visibilityClass
        );
        $this->setVisibilityChoices(
            $targetEntity,
            $config,
            '[filters][columns][visibility][options][field_options]',
            $visibilityClass
        );
    }

    /**
     * @param object $targetEntity
     * @param DatagridConfiguration $config
     * @param string $path
     * @param string $visibilityClass
     */
    protected function setVisibilityChoices($targetEntity, DatagridConfiguration $config, $path, $visibilityClass)
    {
        $pathConfig = $config->offsetGetByPath($path);
        $pathConfig['choices'] = $this->visibilityChoicesProvider->getFormattedChoices($visibilityClass, $targetEntity);
        $config->offsetSetByPath($path, $pathConfig);
    }

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        $parameters = $event->getDatagrid()->getParameters();
        $datagridName = $event->getDatagrid()->getName();
        $targetEntity = $this->getEntity(
            $parameters->get('target_entity_id'),
            $this->subscribedGridConfig[$datagridName]['targetEntityClass']
        );

        if (!$this->isFilteredByDefaultValue(
            $targetEntity,
            $parameters,
            $this->subscribedGridConfig[$datagridName]['visibilityEntityClass']
        )) {
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
     * @param object $targetEntity
     * @param ParameterBag $params
     * @param string $visibilityClass
     * @return bool
     */
    protected function isFilteredByDefaultValue($targetEntity, ParameterBag $params, $visibilityClass)
    {
        if (!$params->get('_filter')) {
            return false;
        }

        foreach ($params->get('_filter')['visibility']['value'] as $value) {
            if ($this->isDefaultValue($targetEntity, $value, $visibilityClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     *
     * @param object $targetEntity
     * @param string $visibilityClass
     * @return bool
     */
    protected function isDefaultValue($targetEntity, $value, $visibilityClass)
    {
        /** @var string $defaultValue */
        $defaultValue = call_user_func([$visibilityClass, 'getDefault'], $targetEntity);

        return $defaultValue === $value;
    }

    /**
     * @param integer|null $entityId
     * @param string $entityClassName
     * @return null|object
     */
    protected function getEntity($entityId, $entityClassName)
    {
        $entity = null;
        if ($entityId) {
            $entity = $this->registry
                ->getRepository($entityClassName)
                ->find($entityId);
        }

        return $entity;
    }
}
