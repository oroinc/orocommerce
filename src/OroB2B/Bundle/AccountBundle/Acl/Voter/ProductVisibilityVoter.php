<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductVisibilityVoter extends AbstractEntityVoter implements ContainerAwareInterface
{

    const ATTRIBUTE_VIEW = 'VIEW';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_VIEW,
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ProductVisibilityQueryBuilderModifier
    */
    private $modifier;
    /**
     * @param string $class
     * @param int $identifier
     * @param string $attribute
     * @return int
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if (in_array($attribute, $this->supportedAttributes)) {
            $qb = $this->doctrineHelper
                ->getEntityRepository($class)
                ->createQueryBuilder('product')
                ->andWhere('product.id = :id')
                ->setParameter('id', $identifier);
            $this->modifier->modify($qb);
            $product = $qb->getQuery()->getOneOrNullResult();

            if ($product) {
                return self::ACCESS_GRANTED;
            }

            return self::ACCESS_DENIED;
        }

        return self::ACCESS_DENIED;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Sets the Container.
     *
     * @param ProductVisibilityQueryBuilderModifier $modifier A ProductVisibilityQueryBuilderModifier instance
     */
    public function setModifier(ProductVisibilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }


    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, 'OroB2B\Bundle\ProductBundle\Entity\Product', true);
    }
}
