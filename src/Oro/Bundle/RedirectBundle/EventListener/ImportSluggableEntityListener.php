<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
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

    public function onProcessAfter(StrategyEvent $event): void
    {
        $entity = $event->getEntity();
        // In order for the SluggableEntityListener to process the new slugs, modify datetime field to trigging
        // to the UnitOfWork (see LocalizedSlugType::updateDateTime).
        if ($entity instanceof SluggableInterface && $entity instanceof UpdatedAtAwareInterface) {
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
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
