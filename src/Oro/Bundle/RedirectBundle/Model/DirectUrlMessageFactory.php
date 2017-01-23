<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectUrlMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'class';

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @var ManagerRegistry
     */
    private $registry;

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
    public function createMessage(SluggableInterface $entity)
    {
        return [
            self::ID => $entity->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($entity),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createMassMessage($entityClass, $id)
    {
        return [
            self::ID => $id,
            self::ENTITY_CLASS_NAME => $entityClass
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntitiesFromMessage($data)
    {
        $data = $this->getResolvedData($data);
        $className = $data[self::ENTITY_CLASS_NAME];

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($className);
        $metadata = $em->getClassMetadata($className);
        $idFieldName = $metadata->getSingleIdentifierFieldName();

        return $em->getRepository($className)
            ->findBy([$idFieldName => $data[self::ID]]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassFromMessage($data)
    {
        $data = $this->getResolvedData($data);

        return $data[self::ENTITY_CLASS_NAME];
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
                    self::ENTITY_CLASS_NAME
                ]
            );

            $resolver->setAllowedTypes(self::ID, ['int', 'array']);
            $resolver->setAllowedTypes(self::ENTITY_CLASS_NAME, 'string');
            $resolver->setAllowedValues(
                self::ENTITY_CLASS_NAME,
                function ($className) {
                    return class_exists($className) && is_a($className, SluggableInterface::class, true);
                }
            );

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getResolvedData($data)
    {
        try {
            return $this->getOptionsResolver()->resolve($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
