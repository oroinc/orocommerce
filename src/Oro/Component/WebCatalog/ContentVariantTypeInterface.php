<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Represents a content variant type for web catalog content nodes.
 *
 * See {@see \Oro\Bundle\FrontendBundle\Api\ResourceTypeResolverInterface}
 * and {@see \Oro\Bundle\FrontendBundle\Api\ResourceApiUrlResolverInterface}
 * to provide a resource type and API URL for a content variant.
 */
interface ContentVariantTypeInterface
{
    /**
     * Gets the name of this content variant type.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the translatable label for this content variant type title.
     * Rendered on "Add ..." variant button.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Gets the form type class responsible to edit the content variant details.
     *
     * @return string
     */
    public function getFormType();

    /**
     * Checks whether this content variant type is allowed to be added to web catalog content nodes.
     * Here some ACL checks may be performed.
     *
     * @return bool
     */
    public function isAllowed();

    /**
     * Gets routing data for the given content variant instance.
     *
     * @param ContentVariantInterface $contentVariant
     *
     * @return RouteData
     */
    public function getRouteData(ContentVariantInterface $contentVariant);

    /**
     * Gets the class name of an entity for API resource for this content variant type.
     *
     * @return string
     */
    public function getApiResourceClassName();

    /**
     * Gets DQL expression that should be added to SELECT part of ORM query
     * to get the identifier of an entity for API resource for this content variant type.
     *
     * @param string $alias The alias for ContentVariant entity in ORM query
     *
     * @return string
     */
    public function getApiResourceIdentifierDqlExpression($alias);
}
