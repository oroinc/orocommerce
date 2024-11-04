<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * ORM search for ProductFamily by default label
 */
class ProductFamilySearchHandler implements SearchHandlerInterface
{
    private ManagerRegistry $registry;
    private PropertyAccessorInterface $propertyAccessor;
    private AclHelper $aclHelper;
    private EntityNameResolver $entityNameResolver;

    public function __construct(
        ManagerRegistry $registry,
        PropertyAccessorInterface $propertyAccessor,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver
    ) {
        $this->registry = $registry;
        $this->propertyAccessor = $propertyAccessor;
        $this->aclHelper = $aclHelper;
        $this->entityNameResolver = $entityNameResolver;
    }

    #[\Override]
    public function search($query, $page, $perPage, $searchById = false)
    {
        $qb = $this->registry->getRepository(AttributeFamily::class)
            ->createQueryBuilder('af')
            ->orderBy('af.code');

        if ($searchById) {
            $qb->where($qb->expr()->in('af.id', ':ids'))
                ->setParameter('ids', array_filter(explode(',', $query)));
        } else {
            $qb->innerJoin('af.labels', 'labels', Join::WITH, $qb->expr()->isNull('labels.localization'))
                ->where($qb->expr()->eq('af.entityClass', ':entityClass'))
                ->setParameter('entityClass', Product::class);

            if ($query) {
                $qb->andWhere($qb->expr()->like($qb->expr()->lower('labels.string'), ':label'));
                $qb->setParameter('label', '%' . strtolower(trim($query)) . '%');
            }
        }

        return [
            'results' => array_map(
                function (AttributeFamily $attributeFamily) {
                    return $this->convertItem($attributeFamily);
                },
                $this->aclHelper->apply($qb)->getResult()
            ),
            'more' => false
        ];
    }

    #[\Override]
    public function getProperties()
    {
        return ['defaultLabel'];
    }

    #[\Override]
    public function getEntityName()
    {
        return AttributeFamily::class;
    }

    #[\Override]
    public function convertItem($item)
    {
        return [
            'id' => $this->propertyAccessor->getValue($item, 'id'),
            'defaultLabel' => $this->entityNameResolver->getName($item)
        ];
    }
}
