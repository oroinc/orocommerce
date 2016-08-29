<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;
use Oro\Bundle\RFPBundle\Entity\RequestStatusTranslation;

abstract class AbstractLoadRequestStatus extends AbstractTranslatableEntityFixture
{
    const PREFIX = 'request_status';

    /**
     * @return array
     */
    abstract protected function getItems();

    /**
     * {@inheritdoc}
     */
    public function loadEntities(ObjectManager $objectManager)
    {
        $localeSettings = $this->container->get('oro_locale.settings');
        $defaultLocale = $localeSettings->getLocale();
        $locales = $this->getTranslationLocales();

        foreach ($this->getItems() as $item) {
            $status = new RequestStatus();
            $status->setSortOrder($item['order']);
            $status->setName($item['name']);

            foreach ($locales as $locale) {
                $label = $this->translate($item['name'], static::PREFIX, $locale);

                if ($locale == $defaultLocale) {
                    $status
                        ->setLabel($label)
                        ->setLocale($locale);
                } else {
                    $status->addTranslation(
                        (new RequestStatusTranslation())->setLocale($locale)->setField('label')->setContent($label)
                    );
                }
            }

            $objectManager->persist($status);
        }

        $objectManager->flush();
    }
}
