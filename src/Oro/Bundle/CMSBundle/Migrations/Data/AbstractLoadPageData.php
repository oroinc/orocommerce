<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class for fixtures that load storefront pages.
 */
abstract class AbstractLoadPageData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    protected array $imagesMap = [];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $organization = $this->getOrganization($manager);

        $slugRedirectGenerator = $this->container->get('oro_redirect.generator.slug_entity');

        $loadedPages = [];
        foreach ((array)$this->getFilePaths() as $filePath) {
            $pages = $this->loadFromFile($manager, $filePath, $organization);
            foreach ($pages as $reference => $page) {
                $manager->persist($page);
                $loadedPages[] = $page;

                $this->setReference($reference, $page);
            }
        }
        $manager->flush();

        foreach ($loadedPages as $page) {
            $slugRedirectGenerator->generate($page, true);
        }

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCacheInterface) {
            $cache->flushAll();
        }
        $manager->flush();
    }

    protected function getOrganization(ObjectManager $manager): Organization
    {
        return $manager->getRepository(Organization::class)->getFirst();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $filePath
     * @param Organization  $organization
     *
     * @return Page[]
     */
    protected function loadFromFile(ObjectManager $manager, string $filePath, Organization $organization): array
    {
        $rows = Yaml::parse(file_get_contents($filePath));
        $pages = [];
        $fileManager = $this->container->get('oro_attachment.file_manager');
        foreach ($rows as $reference => $row) {
            $isPageShouldBeUpdated = $row['update'] ?? false;
            if ($isPageShouldBeUpdated) {
                if ($row['title'] ?? null) {
                    $page = $this->getPageByTitle($manager, $row['title']);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The "title" option is required when "update" flag is set. Page reference: %s.',
                            $reference
                        )
                    );
                }
            } else {
                $page = new Page();
                $page->addTitle((new LocalizedFallbackValue())->setString($row['title']));
                if ($row['slug'] ?? '') {
                    $page->addSlugPrototype((new LocalizedFallbackValue())->setString($row['slug']));
                }
                $page->setOrganization($organization);
            }

            $page->setContent(
                $this->getPageContent($manager, $fileManager, $reference, $row['content'] ?? '')
            );
            if ($row['contentStyle'] ?? '') {
                $page->setContentStyle($row['contentStyle']);
            }

            $pages[$reference] = $page;
        }

        return $pages;
    }

    abstract protected function getFilePaths(): string;

    protected function getFilePathsFromLocator(string $path): array|string
    {
        return $this->container->get('file_locator')->locate($path);
    }

    protected function createDigitalAsset(
        ObjectManager $manager,
        FileManager $fileManager,
        string $sourcePath,
        string $title
    ): DigitalAsset {
        $user = $this->getFirstUser($manager);

        $digitalAssetTitle = new LocalizedFallbackValue();
        $digitalAssetTitle->setString($title);
        $manager->persist($digitalAssetTitle);

        $imagePath = $this->getFilePathsFromLocator($sourcePath);
        $sourceFile = $fileManager->createFileEntity(\is_array($imagePath) ? current($imagePath) : $imagePath);
        $sourceFile->setOwner($user);
        $manager->persist($sourceFile);

        $digitalAsset = new DigitalAsset();
        $digitalAsset->addTitle($digitalAssetTitle);
        $digitalAsset->setSourceFile($sourceFile);
        $digitalAsset->setOwner($user);
        $digitalAsset->setOrganization($user->getOrganization());
        $manager->persist($digitalAsset);

        return $digitalAsset;
    }

    protected function getPageContent(
        ObjectManager $manager,
        $fileManager,
        string $pageReference,
        string $content
    ): string {
        if (!isset($this->imagesMap[$pageReference])) {
            return $content;
        }

        foreach ($this->imagesMap[$pageReference] as $source => $placeholder) {
            $parts = explode('/', $source);

            $digitalAsset = $this->createDigitalAsset(
                $manager,
                $fileManager,
                $source,
                sprintf('%s_%s', $pageReference, array_pop($parts))
            );

            $manager->flush();

            $content = str_replace(
                $placeholder,
                sprintf("{{ wysiwyg_image('%d','%s') }}", $digitalAsset->getId(), UUIDGenerator::v4()),
                $content
            );
        }

        return $content;
    }

    protected function getPageByTitle(ObjectManager $manager, string $title): ?Page
    {
        $qb = $manager->getRepository(Page::class)->createQueryBuilder('page');

        return $qb
            ->innerJoin('page.titles', 'title')
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
