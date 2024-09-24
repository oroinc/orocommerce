<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ProductBundle\ContentWidget\ProductSegmentContentWidgetType;

/**
 * Adds new setting options with default values for existing product segment widgets.
 */
class UpdateProductSegmentWidgetOptions extends AbstractFixture
{
    private static array $widgetSettings = [
        'autoplaySpeed' => 4000,
        'arrows' => true,
        'autoplay' => false,
        'show_arrows_on_touchscreens' => false,
        'dots' => false,
        'infinite' => false
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $widgets = $manager->getRepository(ContentWidget::class)->findBy([
            'widgetType' => ProductSegmentContentWidgetType::getName(),
        ]);

        if (!$widgets) {
            return;
        }

        foreach ($widgets as $widget) {
            $settings = $widget->getSettings();
            foreach (self::$widgetSettings as $name => $value) {
                if (isset($settings[$name])) {
                    continue;
                }

                $settings[$name] = $value;
            }

            $widget->setSettings($settings);
        }

        $manager->flush();
    }
}
