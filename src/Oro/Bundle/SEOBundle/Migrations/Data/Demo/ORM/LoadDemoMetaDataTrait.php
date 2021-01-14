<?php
declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Provides addMetaFieldsData() method to initialize default SEO localized fields.
 */
trait LoadDemoMetaDataTrait
{
    public function addMetaFieldsData(ObjectManager $manager, array $entities): void
    {
        foreach ($entities as $entity) {
            $entity->addMetaDescription($this->getSeoMetaFieldData($manager, 'defaultMetaDescription'));
            $entity->addMetaKeyword($this->getSeoMetaFieldData($manager, 'defaultMetaKeywords'));

            $manager->persist($entity);
        }
    }

    /**
     * Create a new LocalizedFallbackValue, persist it and return it
     */
    protected function getSeoMetaFieldData(
        ObjectManager $manager,
        $seoFieldValue,
        bool $isString = false
    ): LocalizedFallbackValue {
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
