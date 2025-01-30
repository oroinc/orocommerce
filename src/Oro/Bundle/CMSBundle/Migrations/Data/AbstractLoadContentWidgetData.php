<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstract class for content widgets data fixture.
 */
abstract class AbstractLoadContentWidgetData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface,
    VersionedFixtureInterface
{
    protected ?ContainerInterface $container = null;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class
        ];
    }

    abstract protected function getFilePaths(): string;

    abstract protected function updateContentWidget(
        ObjectManager $manager,
        ContentWidget $contentWidget,
        array $row
    ): void;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $organization = $this->getOrganization($manager);

        foreach ((array)$this->getFilePaths() as $filePath) {
            $contentWidgets = $this->loadFromFile($manager, $filePath, $organization);
            foreach ($contentWidgets as $reference => $contentWidget) {
                $this->setReference($reference, $contentWidget);
                $manager->persist($contentWidget);
            }
        }
        $manager->flush();
    }

    protected function getOrganization(ObjectManager $manager): Organization
    {
        return $manager->getRepository(Organization::class)->getFirst();
    }

    /**
     * @return ContentWidget[]
     */
    protected function loadFromFile(ObjectManager $manager, string $filePath, Organization $organization): array
    {
        $rows = Yaml::parse(file_get_contents($filePath));
        $contentWidgets = [];
        foreach ($rows as $reference => $row) {
            $contentWidget = $this->findContentWidget($manager, $row, $organization);
            if (!$contentWidget) {
                $contentWidget = new ContentWidget();
                $contentWidget->setName($row['name']);
                $contentWidget->setWidgetType($row['type']);
                $contentWidget->setOrganization($organization);
            }

            $contentWidget->setDescription($row['description'] ?? null);
            $contentWidget->setLayout($row['layout'] ?? null);
            $contentWidget->setSettings($row['settings'] ?? []);

            if (isset($row['label'])) {
                $contentWidget->setDefaultLabel($row['label']);
            }

            $this->updateContentWidget($manager, $contentWidget, $row);

            $contentWidgets[$reference] = $contentWidget;
        }

        return $contentWidgets;
    }

    protected function findContentWidget(ObjectManager $manager, array $row, Organization $organization): ?ContentWidget
    {
        if (empty($row['name'])) {
            return null;
        }

        return $manager->getRepository(ContentWidget::class)->findOneBy([
            'name' => $row['name'],
            'organization' => $organization
        ]);
    }

    protected function getFilePathsFromLocator(string $path): string
    {
        $locator = $this->container->get('file_locator');
        return $locator->locate($path);
    }
}
