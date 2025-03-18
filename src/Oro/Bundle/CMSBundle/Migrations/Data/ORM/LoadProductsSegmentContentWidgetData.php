<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadContentWidgetData;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadFeaturedProductsSegmentData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadNewArrivalProductsSegmentData;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Class to load "product_segment" content widget's data.
 */
class LoadProductsSegmentContentWidgetData extends AbstractLoadContentWidgetData
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadFeaturedProductsSegmentData::class,
            LoadNewArrivalProductsSegmentData::class,
            LoadGlobalThemeConfigurationData::class
        ];
    }

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/content_widgets.yml');
    }

    #[\Override]
    protected function updateContentWidget(ObjectManager $manager, ContentWidget $contentWidget, array $row): void
    {
        $settings = $contentWidget->getSettings();
        if (isset($settings['segment'])) {
            $segment = $this->getSegment($manager, $settings['segment']);
            $settings['segment'] = $segment?->getId() ?? $settings['segment'];

            $contentWidget->setSettings($settings);
        }
    }

    #[\Override]
    public function getVersion(): string
    {
        return '1.0';
    }

    private function getSegment(ObjectManager $manager, string $parameter): ?Segment
    {
        $name = $this->container->getParameter($parameter);
        return $manager->getRepository(Segment::class)->findOneBy(['name' => $name]);
    }
}
