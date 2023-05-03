<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

/**
 * Provides the ability to create slugs based on entity source.
 */
class SlugifyEntityHelper
{
    /**
     * @var SlugGenerator
     */
    private $slugGenerator;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        SlugGenerator $slugGenerator,
        ConfigManager $configManager,
        ManagerRegistry $managerRegistry
    ) {
        $this->slugGenerator = $slugGenerator;
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
    }

    public function fill(SluggableInterface $entity): void
    {
        $localizedSources = $this->getSourceField($entity);
        if ($localizedSources) {
            $localizedSlugs = $entity->getSlugPrototypes();
            $this->fillFromSourceField($localizedSources, $localizedSlugs);
        }
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $localizedSources
     * @param Collection|AbstractLocalizedFallbackValue[] $localizedSlugs
     */
    public function fillFromSourceField(Collection $localizedSources, Collection $localizedSlugs): void
    {
        foreach ($localizedSources as $localizedSource) {
            // We are not passing default localized value that is with null location and null string(imported).
            if (!$localizedSource->getString() && $localizedSource->getLocalization() !== null) {
                continue;
            }

            $localizedSlug = $this->getSlugBySource($localizedSlugs, $localizedSource);
            if ($localizedSlug && $this->isSlugEmpty($localizedSlug)) {
                $localizedSlug->setString($this->slugGenerator->slugify($localizedSource->getString()));
                continue;
            }

            if (!$localizedSlug) {
                $localizedSlug = LocalizedFallbackValue::createFromAbstract($localizedSource);
                if ($localizedSource->getString() !== null) {
                    $localizedSlug->setString($this->slugGenerator->slugify($localizedSource->getString()));
                }
                $localizedSlugs->add($localizedSlug);
            }
        }
    }

    public function getSourceFieldName(string $className): ?string
    {
        $provider = $this->configManager->getProvider('slug');
        $config = $provider->getConfig($className);

        return $config->get('source');
    }

    /**
     * @param SluggableInterface $sluggableEntity
     *
     * @return Collection
     */
    private function getSourceField(SluggableInterface $sluggableEntity): ?Collection
    {
        $className = ClassUtils::getClass($sluggableEntity);
        $sourceField = $this->getSourceFieldName($className);
        if (!$sourceField) {
            return null;
        }
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        $classMetadata = $entityManager->getClassMetadata($className);

        return $classMetadata->reflFields[$sourceField]->getValue($sluggableEntity);
    }

    /**
     * Slug is considered empty if there is no text and not have parent or system slug.
     */
    private function isSlugEmpty(AbstractLocalizedFallbackValue $localizedSlug): bool
    {
        return !$localizedSlug->getString() && FallbackType::NONE === $localizedSlug->getFallback();
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $localizedSlugs
     * @param AbstractLocalizedFallbackValue $localizedSource
     *
     * @return null|AbstractLocalizedFallbackValue
     */
    private function getSlugBySource(
        Collection $localizedSlugs,
        AbstractLocalizedFallbackValue $localizedSource
    ): ?AbstractLocalizedFallbackValue {
        foreach ($localizedSlugs as $localizedSlug) {
            if (null === $localizedSource->getLocalization() &&
                null === $localizedSlug->getLocalization()) {
                return $localizedSlug;
            }

            if ($localizedSource->getLocalization() &&
                $localizedSlug->getLocalization() &&
                $localizedSource->getLocalization()->getName() === $localizedSlug->getLocalization()->getName()
            ) {
                return $localizedSlug;
            }
        }

        return null;
    }
}
