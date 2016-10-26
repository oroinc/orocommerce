<?php

namespace Oro\Bundle\AccountBundle\Acl\Voter;

use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;

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
     * @var \Oro\Bundle\ProductBundle\Search\ProductRepository
     */
    protected $productRepository;

    /**
     * @var TokenInterface
     */
    protected $currentToken;

    /**
     * {@inheritdoc}
    */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->currentToken = $token;

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
            $product = $this->productRepository->findOne($identifier);

            if ($product !== null) {
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
     * @param ProductSearchRepository $productRepository
     */
    public function setProductSearchRepository(ProductSearchRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
//
//    protected function modifyQuery(Query $query)
//    {
//        $accountUser = $this->currentToken->getUser();
//        $visibilities = [$this->getProductVisibilityResolvedTerm($queryBuilder)];
//
//        $accountGroup = $this->relationsProvider->getAccountGroup($accountUser);
//        if ($accountGroup) {
//            $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTerm(
//                $queryBuilder,
//                $accountGroup
//            );
//        }
//
//        $account = $this->relationsProvider->getAccount($accountUser);
//        if ($account) {
//            $visibilities[] = $this->getAccountProductVisibilityResolvedTerm($queryBuilder, $account);
//        }
//
//        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
//    }
}
