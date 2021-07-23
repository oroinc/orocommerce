<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeAwareInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;

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
     * @var ScopeManager
     */
    protected $scopeManager;

    public function __construct(
        ManagerRegistry $registry,
        VisibilityChoicesProvider $visibilityChoicesProvider,
        ScopeManager $scopeManager
    ) {
        $this->registry = $registry;
        $this->visibilityChoicesProvider = $visibilityChoicesProvider;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param string $scopeAttr
     * @param string $visibilityEntityClass
     * @param string $targetEntityClass
     */
    public function setGridOptions($scopeAttr, $visibilityEntityClass, $targetEntityClass)
    {
        $this->gridOptions = [
            'visibilityEntityClass' => $visibilityEntityClass,
            'targetEntityClass' => $targetEntityClass,
            'scopeAttr' => $scopeAttr,
        ];
    }

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $visibilityClass = $config->offsetGetByPath('[options][visibilityEntityClass]');
        $params = $event->getParameters();
        $targetEntity = $this->getEntity(
            $params->get('target_entity_id'),
            $config->offsetGetByPath('[options][targetEntityClass]')
        );
        if (is_a($visibilityClass, ScopeAwareInterface::class, true) && $params->has('scope_id')) {
            $selectorPath = '[options][cellSelection][selector]';
            $scopePath = '[scope]';
            $scopeId = $params->get('scope_id');
            $config->offsetSetByPath(
                $selectorPath,
                sprintf('%s-%d', $config->offsetGetByPath($selectorPath), $scopeId)
            );
            $config->offsetSetByPath(
                $scopePath,
                sprintf('%s-%d', $config->offsetGetByPath($scopePath), $scopeId)
            );
        }
        $this->setVisibilityChoices($targetEntity, $config, '[columns][visibility]', $visibilityClass);
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

    public function onDatagridBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $event->getDatagrid()->getParameters();

            if ($parameters->has('scope_id')) {
                $rootScope = $this->registry->getRepository(Scope::class)->find($parameters->get('scope_id'));
            } else {
                $rootScope = $this->scopeManager->findDefaultScope();
            }

            $config = $event->getDatagrid()->getConfig();
            $type = call_user_func([$config->offsetGetByPath('[options][visibilityEntityClass]'), 'getScopeType']);
            $criteria = $this->scopeManager->getCriteriaByScope($rootScope, $type);
            $criteria->applyToJoin(
                $datasource->getQueryBuilder(),
                'scope',
                [$config->offsetGetByPath('[options][scopeAttr]')]
            );
        }
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
