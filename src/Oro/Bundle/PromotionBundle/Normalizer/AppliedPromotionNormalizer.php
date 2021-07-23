<?php

namespace Oro\Bundle\PromotionBundle\Normalizer;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class normalize Promotion entity to array and denormalize array to AppliedPromotionData model.
 */
class AppliedPromotionNormalizer implements NormalizerInterface
{
    const REQUIRED_OPTIONS = [
        'id',
        'rule',
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
     * @param object|PromotionDataInterface $promotion
     * @return array
     */
    public function normalize($promotion)
    {
        if (!$promotion instanceof PromotionDataInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Argument promotion should be instance of %s entity',
                PromotionDataInterface::class
            ));
        }

        $promotionData = [
            'id' => $promotion->getId(),
            'useCoupons' => $promotion->isUseCoupons(),
            'rule' => $this->ruleNormalizer->normalize($promotion->getRule()),
        ];

        if ($promotion->getProductsSegment()) {
            $promotionData['productsSegment'] = $this->segmentNormalizer->normalize($promotion->getProductsSegment());
        }

        $promotionData['scopes'] = [];
        foreach ($promotion->getScopes() as $scope) {
            $promotionData['scopes'][] = $this->scopeNormalizer->normalize($scope);
        }

        return $promotionData;
    }

    /**
     * @param array $promotionData
     * @return AppliedPromotionData
     */
    public function denormalize(array $promotionData)
    {
        $resolver = $this->getOptionResolver();
        $resolver->resolve($promotionData);

        /** @var Rule $rule */
        $rule = $this->ruleNormalizer->denormalize($promotionData['rule']);

        $appliedPromotionData = new AppliedPromotionData();
        $appliedPromotionData->setId($promotionData['id'])
            ->setUseCoupons($promotionData['useCoupons'])
            ->setRule($rule);

        if (array_key_exists('productsSegment', $promotionData)) {
            /** @var Segment $productsSegment */
            $productsSegment = $this->segmentNormalizer->denormalize($promotionData['productsSegment']);

            $appliedPromotionData->setProductsSegment($productsSegment);
        }

        if (!empty($promotionData['scopes'])) {
            foreach ($promotionData['scopes'] as $scopeData) {
                /** @var Scope $scope */
                $scope = $this->scopeNormalizer->denormalize($scopeData);

                if ($scope) {
                    $appliedPromotionData->addScope($scope);
                }
            }
        }

        return $appliedPromotionData;
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(self::REQUIRED_OPTIONS);
        $resolver->setDefined(['productsSegment', 'scopes']);
        $resolver->setDefault('scopes', []);

        $resolver->setAllowedTypes('id', ['integer']);
        $resolver->setAllowedTypes('rule', ['array']);
        $resolver->setAllowedTypes('scopes', ['array']);
        $resolver->setAllowedTypes('useCoupons', ['boolean']);
        $resolver->setAllowedTypes('productsSegment', ['array', 'null']);

        return $resolver;
    }
}
