<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ProductVisibilityVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_VIEW = 'VIEW';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_VIEW,
    ];

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    private $modifier;

    /**
     * @inheritdoc
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if (in_array($attribute, $this->supportedAttributes)) {
            $repository = $this->doctrineHelper
                ->getEntityRepository($class);
            /** @var $repository ProductRepository */
            $qb = $repository->getProductsQueryBuilder([$identifier]);
            $this->modifier->modify($qb);
            $product = $qb->getQuery()->getOneOrNullResult();

            if ($product) {
                return self::ACCESS_GRANTED;
            }

            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
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
}
