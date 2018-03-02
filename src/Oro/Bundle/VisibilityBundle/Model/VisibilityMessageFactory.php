<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Creates array message data for updating visibility of products and categories for customers or customer groups.
 * Also it can restore original visibility entity from generated message.
 */
class VisibilityMessageFactory implements MessageFactoryInterface
{
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
        }

        return [
            self::ID => $visibility->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
        ];
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

        $visibility = $this->getVisibilityEntity($data);

        if (!$visibility && array_key_exists(self::TARGET_CLASS_NAME, $data)) {
            $target = $this->getRepository($data[self::TARGET_CLASS_NAME])
                ->find($data[self::TARGET_ID]);

            $scope = $this->getRepository(Scope::class)
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
                ]
            );
            $resolver->setDefined([
                self::TARGET_CLASS_NAME,
                self::TARGET_ID,
                self::SCOPE_ID,
            ]);
            $resolver->setAllowedTypes(self::ID, ['int', 'null']);
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

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array $data
     * @return null|object
     */
    private function getVisibilityEntity(array $data)
    {
        $visibility = null;

        if ($data[self::ID]) {
            $visibility = $this->getRepository($data[self::ENTITY_CLASS_NAME])
                ->find($data[self::ID]);
        }

        return $visibility;
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    private function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }
}
