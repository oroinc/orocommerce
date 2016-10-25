<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

trait LoadDemoMetaDataTrait
{
    /**
     * @param ObjectManager $manager
     * @param array $entities
     */
    public function addMetaFieldsData(ObjectManager $manager, array $entities)
    {
        foreach ($entities as $entity) {
            $entity->addMetaTitles($this->getSeoMetaFieldData($manager, 'defaultMetaTitle', true));
            $entity->addMetaDescriptions($this->getSeoMetaFieldData($manager, 'defaultMetaDescription', false));
            $entity->addMetaKeywords($this->getSeoMetaFieldData($manager, 'defaultMetaKeywords', false));

            $manager->persist($entity);
        }
    }

    /**
     * Create a new LocalizedFallbackValue, persist it and return it
     *
     * @param ObjectManager $manager
     * @param $seoFieldValue
     * @param bool $isString
     * @return LocalizedFallbackValue
     */
    protected function getSeoMetaFieldData(ObjectManager $manager, $seoFieldValue, $isString)
    {
        // TODO: add migration to move data from string to text field
        $seoField = new LocalizedFallbackValue();
        if ($isString) {
            $seoField->setString($seoFieldValue);
        } else {
            $seoField->setText($seoFieldValue);
        }
        $manager->persist($seoField);

        return $seoField;
    }
}
