<?php

namespace Oro\Bundle\WebCatalogBundle\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * The content variant type for a system page.
 */
class SystemPageContentVariantType implements ContentVariantTypeInterface
{
    const TYPE = 'system_page';

    #[\Override]
    public function getName()
    {
        return self::TYPE;
    }

    #[\Override]
    public function getTitle()
    {
        return 'oro.webcatalog.contentvariant.variant_type.system_page.label';
    }

    #[\Override]
    public function getFormType()
    {
        return SystemPageVariantType::class;
    }

    #[\Override]
    public function isAllowed()
    {
        return true;
    }

    /**
     * @param ContentVariant $contentVariant
     *
     */
    #[\Override]
    public function getRouteData(ContentVariantInterface $contentVariant)
    {
        return new RouteData($contentVariant->getSystemPageRoute());
    }

    #[\Override]
    public function getApiResourceClassName()
    {
        return SystemPage::class;
    }

    #[\Override]
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return $alias . '.systemPageRoute';
    }
}
