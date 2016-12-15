<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;

class WebCatalogContextConfigurator implements ContextConfiguratorInterface
{
    const CONTEXT_VARIABLE = 'web_catalog';

    /**
     * @var ScopeWebCatalogProvider
     */
    protected $scopeWebCatalogProvider;

    /**
     * @param ScopeWebCatalogProvider $scopeWebCatalogProvider
     */
    public function __construct(ScopeWebCatalogProvider $scopeWebCatalogProvider)
    {
        $this->scopeWebCatalogProvider = $scopeWebCatalogProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([WebCatalogContextConfigurator::CONTEXT_VARIABLE])
            ->setAllowedTypes([WebCatalogContextConfigurator::CONTEXT_VARIABLE => ['null', 'string']]);

        $context->set(
            WebCatalogContextConfigurator::CONTEXT_VARIABLE,
            $this->scopeWebCatalogProvider->getCriteriaForCurrentScope()[ScopeWebCatalogProvider::WEB_CATALOG]
        );
    }
}
