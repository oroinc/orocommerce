<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\ContentWidget\ImageSliderContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;

/**
 * Adds `scaling` option to Image Slider Widget settings.
 */
class UpdateImageSliderWidgetOptions extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $widgets = $manager->getRepository(ContentWidget::class)->findBy([
            'widgetType' => ImageSliderContentWidgetType::getName(),
        ]);
        $hasChanges = false;

        foreach ($widgets as $widget) {
            $settings = $widget->getSettings();
            $scaling = $settings['scaling'] ?? null;
            if (!in_array($scaling, ImageSliderContentWidgetType::SCALING_TYPES, true)) {
                $settings['scaling'] = ImageSliderContentWidgetType::SCALING_PROPORTIONAL;
                $widget->setSettings($settings);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $manager->flush();
        }
    }
}
