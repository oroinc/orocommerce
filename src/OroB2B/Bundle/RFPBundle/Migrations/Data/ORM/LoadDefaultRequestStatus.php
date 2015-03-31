<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadDefaultRequestStatus extends AbstractTranslatableEntityFixture
{
    const PREFIX = 'request_status';

    /**
     * @var array
     */
    /*protected $items = [
        ['order' => 10, 'name' => RequestStatus::OPEN],
        ['order' => 20, 'name' => RequestStatus::CLOSED]
    ];*/

    /**
     * {@inheritdoc}
     */
    public function loadEntities(ObjectManager $objectManager)
    {
        /*$locales = $this->getTranslationLocales();
        $requestStatusRepository = $objectManager->getRepository('OroB2BRFPBundle:RequestStatus');

        foreach ($locales as $locale) {
            foreach ($this->items as $item) {
                $status = $requestStatusRepository->findOneBy(['name' => $item['name']]);

                if (!$status) {
                    $status = new RequestStatus();
                    $status->setSortOrder($item['order']);
                }

                $label = $this->translate($item['name'], static::PREFIX, $locale);

                $status
                    ->setLabel($label)
                    ->setLocale($locale);

                $objectManager->persist($status);
            }

            $objectManager->flush();
        }*/
    }
}
