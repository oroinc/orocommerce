<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Acl\Voter;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Disables content block removal if it was used in {@see ThemeConfiguration}.
 */
class ContentBlockVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct($doctrineHelper);
        $this->setClassName(ContentBlock::class);
    }

    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        if (empty($identifier) || BasicPermission::DELETE !== $attribute) {
            return self::ACCESS_ABSTAIN;
        }

        $optionKey = LayoutThemeConfiguration::buildOptionKey('header', 'promotional_content');
        /** @var ThemeConfiguration $themeConfiguration */
        foreach ($this->getAllThemeConfigurations() as $themeConfiguration) {
            if ($themeConfiguration->getConfigurationOption($optionKey) === $identifier) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function getAllThemeConfigurations(): array
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(ThemeConfiguration::class)->findAll();
    }
}
