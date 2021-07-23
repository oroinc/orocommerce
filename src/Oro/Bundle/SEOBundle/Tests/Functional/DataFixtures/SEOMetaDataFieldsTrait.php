<?php
declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\String\Inflector\EnglishInflector;

trait SEOMetaDataFieldsTrait
{
    private ?EnglishInflector $symfonyInflector = null;

    public function loadLocalizedFallbackValues(
        ObjectManager $manager,
        object $entity,
        array $metadataFields = []
    ): void {
        foreach ($metadataFields as $fieldName => $metadataField) {
            $localizedFallbackValue = new LocalizedFallbackValue();
            $localizedFallbackValue->setString($metadataField);

            // see \Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\ExtendEntityGeneratorExtension
            $singular = $this->getInflector()->singularize($fieldName);
            if (\is_array($singular)) {
                $singular = \reset($singular);
            }
            $adderMethod = \sprintf('add%s', \ucfirst($singular));

            $entity->$adderMethod($localizedFallbackValue);
            $manager->persist($localizedFallbackValue);
        }
    }

    private function getInflector(): EnglishInflector
    {
        if (!$this->symfonyInflector) {
            $this->symfonyInflector = new EnglishInflector();
        }

        return $this->symfonyInflector;
    }
}
