<?php

namespace OroB2B\Bundle\PricingBundle\Datagrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;

class ProductSelectionGridExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'products-select-grid-frontend';
    const CURRENCY_KEY = 'currency';

    /**
     * @var bool
     */
    protected $applied = false;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FrontendProductListModifier
     */
    protected $productListModifier;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param FrontendProductListModifier $productListModifier
     */
    public function __construct(TokenStorageInterface $tokenStorage, FrontendProductListModifier $productListModifier)
    {
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

        if ($this->request) {
            $currency = $this->request->get(self::CURRENCY_KEY, null);
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
        return !$this->applied
            && static::SUPPORTED_GRID === $config->getName()
            && ($token = $this->tokenStorage->getToken())
            && $token->getUser() instanceof AccountUser;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
