<?php

namespace Oro\Bundle\VisibilityBundle\Acl\Voter;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Security voter that prevents direct access to the products with disabled visibility
 */
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
     * @var CacheProvider
     */
    private $attributePermissionCache;

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
            if ($this->isVisible($class, $identifier)) {
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

    /**
     * @param CacheProvider $attributePermissionCache
     */
    public function setAttributePermissionCache(CacheProvider $attributePermissionCache)
    {
        $this->attributePermissionCache = $attributePermissionCache;
    }

    /**
     * @param string $class
     * @param int $identifier
     * @return boolean
     */
    private function isVisible($class, $identifier)
    {
        if ($this->attributePermissionCache) {
            $cacheKey = $this->getCacheKey($class, $identifier);
            if ($this->attributePermissionCache->contains($cacheKey)) {
                return $this->attributePermissionCache->fetch($cacheKey);
            }
        }

        /** @var $repository ProductRepository */
        $repository = $this->doctrineHelper->getEntityRepository($class);

        $qb = $repository->getProductsQueryBuilder([$identifier]);
        $this->modifier->modify($qb);

        $qb->resetDQLPart('select')
            ->select('1')
            ->setMaxResults(1);

        $isVisible = !empty($qb->getQuery()->getScalarResult());

        if ($this->attributePermissionCache && isset($cacheKey)) {
            $this->attributePermissionCache->save($cacheKey, $isVisible);
        }

        return $isVisible;
    }

    /**
     * @param string $class
     * @param int $identifier
     * @return string
     */
    private function getCacheKey($class, $identifier)
    {
        return $class . '_' . (string)$identifier;
    }
}
