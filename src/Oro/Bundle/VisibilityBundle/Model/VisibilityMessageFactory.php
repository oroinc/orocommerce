<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisibilityMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';
    const TARGET_CLASS_NAME = 'target_class_name';
    const SCOPE_ID = 'scope_id';
    const TARGET_ID = 'target_id';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage($visibility)
    {
        if ($visibility instanceof VisibilityInterface) {
            return [
                self::ID => $visibility->getId(),
                self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
                self::TARGET_CLASS_NAME => ClassUtils::getClass($visibility->getTargetEntity()),
                self::TARGET_ID => $visibility->getTargetEntity()->getId(),
                self::SCOPE_ID => $visibility->getScope()->getId(),
            ];
        } else {
            return [
                self::ID => $visibility->getId(),
                self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityFromMessage($data)
    {
        try {
            $data = $this->getOptionsResolver()->resolve($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $visibility = $this->registry->getManagerForClass($data[self::ENTITY_CLASS_NAME])
            ->getRepository($data[self::ENTITY_CLASS_NAME])
            ->find($data[self::ID]);

        if (!$visibility && $data[self::TARGET_CLASS_NAME]) {
            $target = $this->registry->getManagerForClass($data[self::TARGET_CLASS_NAME])
                ->getRepository($data[self::TARGET_CLASS_NAME])
                ->find($data[self::TARGET_ID]);

            $scope = $this->registry->getManagerForClass(Scope::class)
                ->getRepository(Scope::class)
                ->find($data[self::SCOPE_ID]);

            if (null === $target) {
                throw new InvalidArgumentException('Target object was not found.');
            }
            if (null === $scope) {
                throw new InvalidArgumentException('Scope object was not found.');
            }

            /** @var VisibilityInterface $visibility */
            $visibility = new $data[self::ENTITY_CLASS_NAME];
            $visibility->setScope($scope);
            $visibility->setTargetEntity($target);
            $visibility->setVisibility($visibility::getDefault($target));
        }

        return $visibility;
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        if (null === $this->resolver) {
            $resolver = new OptionsResolver();
            $resolver->setRequired(
                [
                    self::ID,
                    self::ENTITY_CLASS_NAME,
                    self::TARGET_CLASS_NAME,
                    self::TARGET_ID,
                    self::SCOPE_ID,
                ]
            );

            $resolver->setAllowedTypes(self::ID, 'int');
            $resolver->setAllowedTypes(self::TARGET_ID, 'int');
            $resolver->setAllowedTypes(self::SCOPE_ID, 'int');

            $resolver->setAllowedTypes(self::TARGET_CLASS_NAME, 'string');
            $resolver->setAllowedValues(
                self::TARGET_CLASS_NAME,
                function ($className) {
                    return class_exists($className);
                }
            );

            $resolver->setAllowedTypes(self::ENTITY_CLASS_NAME, 'string');
            $resolver->setAllowedValues(
                self::ENTITY_CLASS_NAME,
                function ($className) {
                    return class_exists($className) && is_a($className, VisibilityInterface::class, true);
                }
            );

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }
}
