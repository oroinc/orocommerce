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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return 'oro.webcatalog.contentvariant.variant_type.system_page.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return SystemPageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return true;
    }

    /**
     * @param ContentVariant $contentVariant
     *
     * {@inheritdoc}
     */
    public function getRouteData(ContentVariantInterface $contentVariant)
    {
        return new RouteData($contentVariant->getSystemPageRoute());
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceClassName()
    {
        return SystemPage::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return $alias . '.systemPageRoute';
    }
}
