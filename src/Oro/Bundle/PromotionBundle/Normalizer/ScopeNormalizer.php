<?php

namespace Oro\Bundle\PromotionBundle\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class convert promotion Scope entity to string and vice versa
 */
class ScopeNormalizer implements NormalizerInterface
{
    const REQUIRED_OPTIONS = [
        'id'
    ];

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Scope $scope
     * @return string
     */
    public function normalize($scope)
    {
        if (!$scope instanceof Scope) {
            throw new \InvalidArgumentException('Argument scope should be instance of Scope entity');
        }

        return ['id' => $scope->getId()];
    }

    /**
     * @param array $scopeData
     * @return Scope|null
     */
    public function denormalize(array $scopeData)
    {
        $resolver = $this->getOptionResolver();
        $resolver->resolve($scopeData);

        return $this->registry->getManagerForClass(Scope::class)->find(Scope::class, $scopeData['id']);
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(self::REQUIRED_OPTIONS);

        $resolver->setAllowedTypes('id', ['integer']);

        return $resolver;
    }
}
