<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Search\ProductRepository;

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
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

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
            $product = $this->productRepository->findOne($identifier);

            if ($product !== null) {
                return self::ACCESS_GRANTED;
            }

            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param ProductRepository $productRepository
     */
    public function setProductSearchRepository(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
}
