<?php

namespace Oro\Bundle\ProductBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

/**
 * Modifies a product query builder to filter products by invisible inventory statuses,
 * because they should not be accessible via API for the storefront.
 * @see \Oro\Bundle\ProductBundle\EventListener\ProductDBQueryRestrictionEventListener
 */
class ProductInventoryStatusQueryModifier implements QueryModifierInterface
{
    private EntityClassResolver $entityClassResolver;
    private ProductVisibilityQueryBuilderModifier $modifier;
    private ConfigManager $configManager;

    public function __construct(
        EntityClassResolver $entityClassResolver,
        ProductVisibilityQueryBuilderModifier $modifier,
        ConfigManager $configManager
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->modifier = $modifier;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        $isSupported = false;
        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            if (Product::class === $this->entityClassResolver->getEntityClass($from->getFrom())) {
                $isSupported = true;
                break;
            }
        }
        if ($isSupported) {
            $this->modifier->modifyByInventoryStatus(
                $qb,
                $this->configManager->get('oro_product.general_frontend_product_visibility')
            );
        }
    }
}
