<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Test\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

/**
 * Contains handy methods for the functional tests working with slugs.
 */
trait SlugAwareTestTrait
{
    protected function getExpectedSlugs(SluggableInterface $entity, int $expectedCount): Collection
    {
        $collection = self::getContainer()
            ->get('oro_redirect.generator.slug_entity')
            ->getSlugsByEntitySlugPrototypes($entity);

        self::assertCount($expectedCount, $collection);

        return $collection;
    }

    protected static function findSlug(Collection $slugs, ?Localization $localization): ?Slug
    {
        return $slugs
            ->filter(static function (Slug $slug) use ($localization) {
                return $slug->getLocalization()?->getId() === $localization?->getId();
            })
            ->first() ?: null;
    }

    protected static function assertSlugEquals(Slug $expectedSlug, Slug $slug): void
    {
        self::assertEquals($expectedSlug->getUrl(), $slug->getUrl());
        self::assertEquals($expectedSlug->getLocalization()?->getId(), $slug->getLocalization()?->getId());
        self::assertEquals($expectedSlug->getSlugPrototype(), $slug->getSlugPrototype());
        self::assertEquals($expectedSlug->getScopesHash(), $slug->getScopesHash());
        self::assertEquals($expectedSlug->getParametersHash(), $slug->getParametersHash());
        self::assertEquals($expectedSlug->getRouteParameters(), $slug->getRouteParameters());
    }

    /**
     * @param Collection<Slug> $expectedSlugs
     * @param SluggableInterface $entity
     */
    protected static function assertSlugs(Collection $expectedSlugs, SluggableInterface $entity): void
    {
        /** @var Slug $expectedSlug */
        foreach ($expectedSlugs as $expectedSlug) {
            $localization = $expectedSlug->getLocalization();
            $slug = self::findSlug($entity->getSlugs(), $localization);
            self::assertNotNull(
                $slug,
                sprintf(
                    'Slug for "%s" localization is not found in entity %s #%d',
                    $localization?->getName() ?? 'system',
                    ClassUtils::getClass($entity),
                    $entity->getId()
                )
            );

            self::assertSlugEquals($expectedSlug, $slug);
        }
    }

    protected static function assertSlugsCache(SluggableInterface $entity): void
    {
        $urlStorageCache = self::getContainer()->get('oro_redirect.url_cache');
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');

        $systemSlug = self::findSlug($entity->getSlugs(), null);
        foreach ($localizationManager->getLocalizations() as $localization) {
            $slug = self::findSlug($entity->getSlugs(), $localization) ?? $systemSlug;

            self::assertEquals(
                $slug->getUrl(),
                $urlStorageCache->getUrl(
                    $slug->getRouteName(),
                    $slug->getRouteParameters(),
                    $localization->getId()
                ),
                sprintf(
                    'Cached URL for slug "%s" and "%s" localization was expected to be present',
                    $slug->getUrl(),
                    $localization->getName()
                )
            );
        }
    }

    protected static function assertNoSlugsCache(SluggableInterface $entity): void
    {
        $urlStorageCache = self::getContainer()->get('oro_redirect.url_cache');
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');

        $systemSlug = self::findSlug($entity->getSlugs(), null);
        foreach ($localizationManager->getLocalizations() as $localization) {
            $slug = self::findSlug($entity->getSlugs(), $localization) ?? $systemSlug;

            self::assertEquals(
                false,
                $urlStorageCache->getUrl(
                    $slug->getRouteName(),
                    $slug->getRouteParameters(),
                    $localization->getId()
                ),
                sprintf(
                    'Cached URL for slug "%s" and "%s" localization was expected to be absent',
                    $slug->getUrl(),
                    $localization->getName()
                )
            );
        }
    }
}
