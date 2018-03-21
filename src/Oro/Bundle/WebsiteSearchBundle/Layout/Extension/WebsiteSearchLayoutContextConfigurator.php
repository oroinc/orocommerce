<?php

namespace Oro\Bundle\WebsiteSearchBundle\Layout\Extension;

use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class WebsiteSearchLayoutContextConfigurator implements ContextConfiguratorInterface
{
    public const SEARCH_QUERY_STRING = 'search_query_string';

    /** @var QueryStringProvider */
    protected $queryStringProvider;

    /**
     * @param QueryStringProvider $queryStringProvider
     */
    public function __construct(QueryStringProvider $queryStringProvider)
    {
        $this->queryStringProvider = $queryStringProvider;
    }

    /**
     * @param ContextInterface $context
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([self::SEARCH_QUERY_STRING])
            ->setAllowedTypes(self::SEARCH_QUERY_STRING, ['null', 'string']);

        if ($this->queryStringProvider->getSearchQueryString()) {
            $context->set(
                self::SEARCH_QUERY_STRING,
                $this->queryStringProvider->getSearchQueryString()
            );

            return;
        }

        $context->set(self::SEARCH_QUERY_STRING, null);
    }
}
