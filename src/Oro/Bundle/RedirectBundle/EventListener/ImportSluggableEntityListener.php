<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;

/**
 * Sets slug to entity if source exists and slug empty.
 */
class ImportSluggableEntityListener
{
    /**
     * @var SlugifyEntityHelper
     */
    private $slugifyEntityHelper;

    public function __construct(SlugifyEntityHelper $slugifyEntityHelper)
    {
        $this->slugifyEntityHelper = $slugifyEntityHelper;
    }

    public function onProcessBefore(StrategyEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof SluggableInterface) {
            $this->slugifyEntityHelper->fill($entity);

            $itemData = $event->getContext()->getValue('itemData');
            if (!$itemData) {
                return;
            }

            foreach ($entity->getSlugPrototypes() as $slugPrototype) {
                $localizationCode = LocalizationCodeFormatter::formatName($slugPrototype->getLocalization());
                $slugPrototypes = $itemData['slugPrototypes'][$localizationCode] ?? [];
                if (empty($slugPrototypes['string']) && empty($slugPrototypes['fallback'])) {
                    $slugPrototypes = [
                        'string' => $slugPrototype->getString(),
                        'fallback' => $slugPrototype->getFallback(),
                    ];
                }

                $itemData['slugPrototypes'][$localizationCode] = $slugPrototypes;
            }

            $event->getContext()->setValue('itemData', $itemData);
        }
    }
}
