<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectUrlMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'class';
    const CREATE_REDIRECT = 'createRedirect';

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage(SluggableInterface $entity)
    {
        return [
            self::ID => $entity->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($entity),
            self::CREATE_REDIRECT => $this->isCreateRedirect($entity),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createMassMessage($entityClass, $id, $createRedirect)
    {
        return [
            self::ID => $id,
            self::ENTITY_CLASS_NAME => $entityClass,
            self::CREATE_REDIRECT => $createRedirect
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
     * {@inheritdoc}
     */
    public function getCreateRedirectFromMessage($data)
    {
        $data = $this->getResolvedData($data);

        return $data[self::CREATE_REDIRECT];
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
            $resolver->setDefault(self::CREATE_REDIRECT, true);

            $resolver->setAllowedTypes(self::ID, ['int', 'array']);
            $resolver->setAllowedTypes(self::ENTITY_CLASS_NAME, 'string');
            $resolver->setAllowedTypes(self::CREATE_REDIRECT, 'bool');
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

    /**
     * @param SluggableInterface $entity
     * @return bool
     */
    protected function isCreateRedirect(SluggableInterface $entity)
    {
        $redirectGenerationStrategy = $this->configManager->get('oro_redirect.redirect_generation_strategy');
        if ($redirectGenerationStrategy === Configuration::STRATEGY_ALWAYS) {
            return true;
        }

        if ($redirectGenerationStrategy === Configuration::STRATEGY_NEVER) {
            return false;
        }

        return $entity->getSlugPrototypesWithRedirect()->getCreateRedirect();
    }
}
