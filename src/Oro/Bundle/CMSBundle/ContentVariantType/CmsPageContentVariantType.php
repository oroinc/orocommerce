<?php

namespace Oro\Bundle\CMSBundle\ContentVariantType;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType;
use Oro\Component\Routing\RouteData;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The content variant type for a CMS page.
 */
class CmsPageContentVariantType implements ContentVariantTypeInterface, ContentVariantEntityProviderInterface
{
    const TYPE = 'cms_page';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->propertyAccessor = $propertyAccessor;
    }

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
        return 'oro.cms.page.entity_label';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return CmsPageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return $this->authorizationChecker->isGranted('oro_cms_page_view');
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteData(ContentVariantInterface $contentVariant)
    {
        /** @var Page $cmsPage */
        $cmsPage = $this->getAttachedEntity($contentVariant);

        return new RouteData('oro_cms_frontend_page_view', ['id' => $cmsPage->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceClassName()
    {
        return Page::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResourceIdentifierDqlExpression($alias)
    {
        return sprintf('IDENTITY(%s.cms_page)', $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachedEntity(ContentVariantInterface $contentVariant)
    {
        return $this->propertyAccessor->getValue($contentVariant, 'cmsPage');
    }
}
