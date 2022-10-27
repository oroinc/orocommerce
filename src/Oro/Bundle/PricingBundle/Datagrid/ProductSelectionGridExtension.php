<?php

namespace Oro\Bundle\PricingBundle\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProductSelectionGridExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'products-select-grid-frontend';
    const CURRENCY_KEY = 'currency';

    /**
     * @var bool
     */
    protected $applied = false;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FrontendProductListModifier
     */
    protected $productListModifier;

    public function __construct(
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        FrontendProductListModifier $productListModifier
    ) {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->productListModifier = $productListModifier;
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (!$this->isApplicable($config) || !$datasource instanceof OrmDatasource) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $currency = $request->get(self::CURRENCY_KEY, null);
        } else {
            $currency = null;
        }

        /** @var OrmDatasource $datasource */
        $qb = $datasource->getQueryBuilder();
        $this->productListModifier->applyPriceListLimitations($qb, $currency);

        $this->applied = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && !$this->applied
            && static::SUPPORTED_GRID === $config->getName()
            && ($token = $this->tokenStorage->getToken())
            && $token->getUser() instanceof CustomerUser;
    }
}
