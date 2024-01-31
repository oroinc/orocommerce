<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (ContentTemplate::class === $entityClass) {
            $contentTemplate = new ContentTemplate();
            $contentTemplate->setOrganization($repository->getReference('organization'));
            $contentTemplate->setOwner($repository->getReference('user'));
            $contentTemplate->setName('Test Content Template');
            $repository->setReference('contentTemplate', $contentTemplate);
            $em->persist($contentTemplate);
            $em->flush();

            return ['contentTemplate'];
        }

        if (ContentWidget::class === $entityClass) {
            $contentWidget = new ContentWidget();
            $contentWidget->setOrganization($repository->getReference('organization'));
            $contentWidget->setWidgetType('test_widget');
            $contentWidget->setName('Test Content Widget');
            $repository->setReference('contentWidget', $contentWidget);
            $em->persist($contentWidget);
            $em->flush();

            return ['contentWidget'];
        }

        if (Page::class === $entityClass) {
            $page = new Page();
            $page->setOrganization($repository->getReference('organization'));
            $page->addTitle($this->createLocalizedFallbackValue($em, 'Test Page'));
            $page->addTitle($this->createLocalizedFallbackValue(
                $em,
                'Test Page (de_DE)',
                $repository->getReference('de_DE')
            ));
            $page->addTitle($this->createLocalizedFallbackValue(
                $em,
                'Test Page (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $page->setContent('Test Page Content');
            $repository->setReference('page', $page);
            $em->persist($page);
            $em->flush();

            return ['page'];
        }

        if (ImageSlide::class === $entityClass) {
            $contentWidget = new ContentWidget();
            $contentWidget->setOrganization($repository->getReference('organization'));
            $contentWidget->setWidgetType('test_widget');
            $contentWidget->setName('Test Content Widget for Image Slide');
            $em->persist($contentWidget);
            $imageSlide = new ImageSlide();
            $imageSlide->setOrganization($repository->getReference('organization'));
            $imageSlide->setContentWidget($contentWidget);
            $imageSlide->setAltImageText('Test Image Slide');
            $imageSlide->setText('test text');
            $imageSlide->setUrl('http://example.com');
            $repository->setReference('imageSlide', $imageSlide);
            $em->persist($imageSlide);
            $em->flush();

            return ['imageSlide'];
        }

        if (TabbedContentItem::class === $entityClass) {
            $contentWidget = new ContentWidget();
            $contentWidget->setOrganization($repository->getReference('organization'));
            $contentWidget->setWidgetType('test_widget');
            $contentWidget->setName('Test Content Widget for Tabbed Content Item');
            $em->persist($contentWidget);
            $tabbedContentItem = new TabbedContentItem();
            $tabbedContentItem->setOrganization($repository->getReference('organization'));
            $tabbedContentItem->setContentWidget($contentWidget);
            $tabbedContentItem->setTitle('Test Tabbed Content Item');
            $tabbedContentItem->setContent('test content');
            $tabbedContentItem->setContentStyle('test style');
            $repository->setReference('tabbedContentItem', $tabbedContentItem);
            $em->persist($tabbedContentItem);
            $em->flush();

            return ['tabbedContentItem'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (ContentTemplate::class === $entityClass) {
            return 'Test Content Template';
        }
        if (ContentWidget::class === $entityClass) {
            return 'Test Content Widget';
        }
        if (Page::class === $entityClass) {
            return 'Localization de_DE' === $locale
                ? 'Test Page (de_DE)'
                : 'Test Page';
        }
        if (ImageSlide::class === $entityClass) {
            return 'Test Image Slide';
        }
        if (TabbedContentItem::class === $entityClass) {
            return 'Test Tabbed Content Item';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createLocalizedFallbackValue(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): LocalizedFallbackValue {
        $lfv = new LocalizedFallbackValue();
        $lfv->setString($value);
        if (null !== $localization) {
            $lfv->setLocalization($localization);
        }
        $em->persist($lfv);

        return $lfv;
    }
}
