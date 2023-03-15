<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
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
    /** @var ManagerRegistry */
    private $registry;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var AclHelper */
    private $aclHelper;

    public function __construct(
        ManagerRegistry $registry,
        PropertyAccessorInterface $propertyAccessor,
        AclHelper $aclHelper
    ) {
        $this->registry = $registry;
        $this->propertyAccessor = $propertyAccessor;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return ['defaultLabel'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return AttributeFamily::class;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = ['id' => $this->propertyAccessor->getValue($item, 'id')];

        foreach ($this->getProperties() as $property) {
            $result[$property] = (string) $this->propertyAccessor->getValue($item, $property);
        }

        return $result;
    }
}
