<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatusTranslation;

class LoadDefaultRequestStatus extends AbstractTranslatableEntityFixture
{
    const PREFIX = 'request_status';

    /**
     * @var array
     */
    protected $items = [
        ['order' => 10, 'name' => RequestStatus::OPEN],
        ['order' => 20, 'name' => RequestStatus::CLOSED]
    ];

    /**
     * {@inheritdoc}
     */
    public function loadEntities(ObjectManager $objectManager)
    {
        $localeSettings = $this->container->get('oro_locale.settings');
        $defaultLocale  = $localeSettings->getLocale();
        $locales        = $this->getTranslationLocales();

        if (!in_array($defaultLocale, $locales)) {
            throw new \LogicException('There are no default locale in translations!');
        }

        foreach ($this->items as $item) {
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
