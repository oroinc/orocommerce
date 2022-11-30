<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Message factory for interaction with Direct URL related MQ message data.
 */
class DirectUrlMessageFactory implements MessageFactoryInterface
{
    public const ID = 'id';
    public const ENTITY_CLASS_NAME = 'class';
    public const CREATE_REDIRECT = 'createRedirect';
    public const JOB_ID = 'jobId';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigManager
     */
    private $configManager;

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
        $createRedirect = true;
        if ($entity->getSlugPrototypesWithRedirect()) {
            $createRedirect = $entity->getSlugPrototypesWithRedirect()->getCreateRedirect();
        }

        return [
            self::ID => $entity->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($entity),
            self::CREATE_REDIRECT => $createRedirect,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createMassMessage($entityClass, $id, $createRedirect = true)
    {
        return [
            self::ID => $id,
            self::ENTITY_CLASS_NAME => $entityClass,
            self::CREATE_REDIRECT => $createRedirect,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntitiesFromMessage($data)
    {
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
        return $data[self::ENTITY_CLASS_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateRedirectFromMessage($data)
    {
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
            $resolver->setDefined(self::JOB_ID);

            $resolver->setAllowedTypes(self::ID, ['int', 'array']);
            $resolver->setAllowedTypes(self::ENTITY_CLASS_NAME, 'string');
            $resolver->setAllowedTypes(self::CREATE_REDIRECT, 'bool');

            $resolver->setAllowedValues(
                self::ENTITY_CLASS_NAME,
                function ($className) {
                    return class_exists($className) && is_a($className, SluggableInterface::class, true);
                }
            );

            $resolver->setNormalizer(
                self::CREATE_REDIRECT,
                function (Options $options, $value) {
                    $strategy = $this->configManager->get('oro_redirect.redirect_generation_strategy');
                    if ($strategy === Configuration::STRATEGY_ALWAYS) {
                        return true;
                    }

                    if ($strategy === Configuration::STRATEGY_NEVER) {
                        return false;
                    }

                    return $value;
                }
            );

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array|string $data
     * @return array
     */
    protected function getResolvedData($data)
    {
        try {
            if (!$data) {
                throw new InvalidArgumentException('Messages is empty');
            }
            // BC for old message format support where ClassName was passed as message
            if (is_string($data)) {
                $data = [self::ENTITY_CLASS_NAME => $data, self::ID => []];
            }

            return $this->getOptionsResolver()->resolve($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
