<?php

namespace Oro\Bundle\PromotionBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FrontendBundle\Datagrid\Extension\FrontendDatagridExtension;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Grid extension that allows to build datagrid on the front store during the coupon validation.
 * But direct access to segment's data is protected by ACL resources.
 */
class FrontendSegmentDatagridExtension extends AbstractExtension
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FrontendHelper $frontendHelper
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $this->isSegmentGrid($config) && $this->isProductEntityInGridQuery($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        if (!$this->frontendHelper->isFrontendRequest()) {
            return;
        }

        $config->offsetSetByPath(FrontendDatagridExtension::FRONTEND_OPTION_PATH, true);
    }

    /**
     * @param DatagridConfiguration $config
     * @return bool
     */
    private function isSegmentGrid(DatagridConfiguration $config)
    {
        return str_starts_with($config->offsetGet('name'), Segment::GRID_PREFIX);
    }

    /**
     * @param DatagridConfiguration $config
     * @return bool
     */
    private function isProductEntityInGridQuery(DatagridConfiguration $config)
    {
        return $config->getOrmQuery()->getRootEntity() === Product::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }
}
