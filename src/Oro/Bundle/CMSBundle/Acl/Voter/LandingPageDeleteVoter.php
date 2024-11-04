<?php

namespace Oro\Bundle\CMSBundle\Acl\Voter;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

/**
 * Prevents removal of landing page that is selected as:
 * - homepage in system configuration
 * - or a content variant in any content node of any web catalog.
 */
class LandingPageDeleteVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct($doctrineHelper);
        $this->setClassName(Page::class);
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        if ($this->isSelectedInSystemConfiguration($identifier) ||
            $this->isSelectedAsContentVariant($identifier)
        ) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isSelectedInSystemConfiguration(int $identifier): bool
    {
        return (bool)$this->doctrineHelper
            ->getEntityRepository(ConfigValue::class)
            ->findBy([
                'section' => Configuration::ROOT_NAME,
                'name' => Configuration::HOME_PAGE,
                'textValue' => $identifier
            ]);
    }

    private function isSelectedAsContentVariant(int $identifier): bool
    {
        return (bool)$this->doctrineHelper
            ->getEntityRepository(ContentVariant::class)
            ->findBy([
                'type' => CmsPageContentVariantType::TYPE,
                'cms_page' => $identifier
            ]);
    }
}
