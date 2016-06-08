<?php

namespace OroB2B\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

trait LoadDemoMetaDataTrait
{
    public function addMetaFieldsData(ObjectManager $manager, array $entities)
    {
        foreach ($entities as $entity) {
            $entity->addMetaTitles($this->getSeoMetaFieldData($manager, 'defaultMetaTitle'));
            $entity->addMetaDescriptions($this->getSeoMetaFieldData($manager, 'defaultMetaDescription'));
            $entity->addMetaKeywords($this->getSeoMetaFieldData($manager, 'defaultMetaKeywords'));

            $manager->persist($entity);
        }
    }

    /**
     * Create a new LocalizedFallbackValue, persist it and return it
     *
     * @param ObjectManager $manager
     * @param $seoFieldValue
     * @return LocalizedFallbackValue
     */
    public function getSeoMetaFieldData(ObjectManager $manager, $seoFieldValue)
    {
        $seoField = new LocalizedFallbackValue();
        $seoField->setString($seoFieldValue);
        $manager->persist($seoField);

        return $seoField;
    }
}
