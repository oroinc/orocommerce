<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * The scope criteria provider for the current web catalog.
 */
class ScopeWebCatalogProvider implements ScopeCriteriaProviderInterface
{
    public const WEB_CATALOG = 'webCatalog';

    /** @var WebCatalogProvider */
    private $webCatalogProvider;

    public function __construct(WebCatalogProvider $webCatalogProvider)
    {
        $this->webCatalogProvider = $webCatalogProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaField()
    {
        return self::WEB_CATALOG;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValue()
    {
        return $this->webCatalogProvider->getWebCatalog();
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return WebCatalog::class;
    }
}
