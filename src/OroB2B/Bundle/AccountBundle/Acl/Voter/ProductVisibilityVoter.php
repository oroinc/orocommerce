<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
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
    protected $modifier;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * {@inheritdoc}
    */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($this->frontendHelper && $this->frontendHelper->isFrontendRequest()) {
            return parent::vote($token, $object, $attributes);
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @inheritdoc
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if (in_array($attribute, $this->supportedAttributes, true)) {
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
     * @param ProductVisibilityQueryBuilderModifier $modifier A ProductVisibilityQueryBuilderModifier instance
     */
    public function setModifier(ProductVisibilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }
}
