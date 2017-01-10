<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class ScopeWebCatalogProvider extends AbstractScopeCriteriaProvider
{
    const WEB_CATALOG = 'webCatalog';

    /**
     * @var WebCatalogProvider
     */
    protected $webCatalogProvider;

    /**
     * @param WebCatalogProvider $webCatalogProvider
     */
    public function __construct(WebCatalogProvider $webCatalogProvider)
    {
        $this->webCatalogProvider = $webCatalogProvider;
    }

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $webCatalog = $this->webCatalogProvider->getWebCatalog();

        return [self::WEB_CATALOG => $webCatalog];
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaField()
    {
        return static::WEB_CATALOG;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return WebCatalog::class;
    }
}
