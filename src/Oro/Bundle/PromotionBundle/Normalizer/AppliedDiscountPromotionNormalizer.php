<?php

namespace Oro\Bundle\PromotionBundle\Normalizer;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This serializer serialize Promotion entity to array and unserialize array to AppliedPromotion model.
 */
class AppliedDiscountPromotionNormalizer implements NormalizerInterface
{
    const REQUIRED_OPTIONS = [
        'id',
        'rule',
        'scopes',
        'useCoupons'
    ];

    /**
     * @var NormalizerInterface
     */
    private $ruleNormalizer;

    /**
     * @var NormalizerInterface
     */
    private $scopeNormalizer;

    /**
     * @var NormalizerInterface
     */
    private $segmentNormalizer;

    /**
     * @param NormalizerInterface $ruleNormalizer
     * @param NormalizerInterface $scopeNormalizer
     * @param NormalizerInterface $segmentNormalizer
     */
    public function __construct(
        NormalizerInterface $ruleNormalizer,
        NormalizerInterface $scopeNormalizer,
        NormalizerInterface $segmentNormalizer
    ) {
        $this->ruleNormalizer = $ruleNormalizer;
        $this->scopeNormalizer = $scopeNormalizer;
        $this->segmentNormalizer = $segmentNormalizer;
    }

    /**
     * @param object|Promotion $promotion
     * @return array
     */
    public function normalize($promotion)
    {
        if (!$promotion instanceof Promotion) {
            throw new \InvalidArgumentException('Argument promotion should be instance of Promotion entity');
        }

        $promotionData = [
            'id' => $promotion->getId(),
            'useCoupons' => $promotion->isUseCoupons(),
            'rule' => $this->ruleNormalizer->normalize($promotion->getRule()),
        ];

        if ($promotion->getProductsSegment()) {
            $promotionData['productsSegment'] = $this->segmentNormalizer->normalize($promotion->getProductsSegment());
        }

        foreach ($promotion->getScopes() as $scope) {
            $promotionData['scopes'][] = $this->scopeNormalizer->normalize($scope);
        }

        return $promotionData;
    }

    /**
     * @param array $promotionData
     * @return AppliedPromotion
     */
    public function denormalize(array $promotionData)
    {
        $resolver = $this->getOptionResolver();
        $resolver->resolve($promotionData);

        /** @var Rule $rule */
        $rule = $this->ruleNormalizer->denormalize($promotionData['rule']);

        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setId($promotionData['id'])
            ->setUseCoupons($promotionData['useCoupons'])
            ->setRule($rule);

        if (array_key_exists('productsSegment', $promotionData)) {
            /** @var Segment $productsSegment */
            $productsSegment = $this->segmentNormalizer->denormalize($promotionData['productsSegment']);

            $appliedPromotion->setProductsSegment($productsSegment);
        }

        foreach ($promotionData['scopes'] as $scopeData) {
            /** @var Scope $scope */
            $scope = $this->scopeNormalizer->denormalize($scopeData);

            if ($scope) {
                $appliedPromotion->addScope($scope);
            }
        }

        return $appliedPromotion;
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(self::REQUIRED_OPTIONS);
        $resolver->setDefined('productsSegment');

        $resolver->setAllowedTypes([
            'id' => ['integer'],
            'rule' => ['array'],
            'scopes' => ['array'],
            'useCoupons' => ['boolean'],
            'productsSegment' => ['array', 'null']
        ]);

        return $resolver;
    }
}
