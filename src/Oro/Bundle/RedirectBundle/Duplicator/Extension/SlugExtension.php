<?php

namespace Oro\Bundle\RedirectBundle\Duplicator\Extension;

use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;
use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Duplicator\Extension\AbstractDuplicatorExtension;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Responsible for modifying the Slug type parameter.
 */
class SlugExtension extends AbstractDuplicatorExtension
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getFilter(): Filter
    {
        return new DoctrineEmptyCollectionFilter();
    }

    public function getMatcher(): Matcher
    {
        $source = $this->getContext()->offsetGet('source');
        $properties = $this->getSlugProperties($source);

        return new PropertiesNameMatcher($properties);
    }

    public function isSupport(DraftableInterface $source): bool
    {
        return $source instanceof SlugAwareInterface;
    }

    private function getSlugProperties(DraftableInterface $source): array
    {
        $properties = [];
        $em = $this->managerRegistry->getManager();
        /** @var ClassMetadataInfo $metadata */
        $metadata = $em->getClassMetadata(ClassUtils::getRealClass($source));
        foreach ($metadata->getAssociationMappings() as $name => $fieldMapping) {
            if ($fieldMapping['targetEntity'] === Slug::class) {
                $properties[] = $name;
            }
        }

        return $properties;
    }
}
