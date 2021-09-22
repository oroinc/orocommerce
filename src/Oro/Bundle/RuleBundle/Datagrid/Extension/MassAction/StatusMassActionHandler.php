<?php

namespace Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles a Status mass action
 */
class StatusMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /**
     * @var string
     */
    protected $responseMessage;

    /**
     * @var string
     */
    protected $repositoryClassPath;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param string $responseMessage
     * @param string $repositoryClassPath
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        string $responseMessage,
        string $repositoryClassPath,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->responseMessage = $responseMessage;
        $this->repositoryClassPath = $repositoryClassPath;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $data = $args->getData();

        $massAction = $args->getMassAction();
        $options = $massAction->getOptions()->toArray();
        $this->entityManager->beginTransaction();
        try {
            set_time_limit(0);
            $iteration = $this->handleMethodsConfigsRuleStatuses($options, $data);
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->getResponse($args, $iteration);
    }

    /**
     * @param array $options
     * @param array $data
     * @return int
     */
    protected function handleMethodsConfigsRuleStatuses($options, $data)
    {
        $status = $options['enable'];
        $isAllSelected = $this->isAllSelected($data);
        $iteration = 0;
        $methodsConfigsRuleIds = [];

        if (array_key_exists('values', $data) && !empty($data['values'])) {
            $methodsConfigsRuleIds = explode(',', $data['values']);
        }

        if ($methodsConfigsRuleIds || $isAllSelected) {
            $queryBuilder = $this
                ->entityManager
                ->getRepository($this->repositoryClassPath)
                ->createQueryBuilder('rule');

            if (!$isAllSelected) {
                $queryBuilder->andWhere($queryBuilder->expr()->in('rule.id', ':methodsConfigsRuleIds'))
                    ->setParameter('methodsConfigsRuleIds', $methodsConfigsRuleIds);
            } elseif ($methodsConfigsRuleIds) {
                $queryBuilder->andWhere($queryBuilder->expr()->notIn('rule.id', ':methodsConfigsRuleIds'))
                    ->setParameter('methodsConfigsRuleIds', $methodsConfigsRuleIds);
            }

            $iteration = $this->process($queryBuilder, $status, $iteration);
        }

        return $iteration;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isAllSelected($data)
    {
        return array_key_exists('inset', $data) && $data['inset'] === '0';
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int $entitiesCount
     *
     * @return MassActionResponse
     */
    protected function getResponse(MassActionHandlerArgs $args, $entitiesCount = 0)
    {
        $massAction      = $args->getMassAction();
        $responseMessage = $massAction->getOptions()->offsetGetByPath('[messages][success]', $this->responseMessage);

        $successful = $entitiesCount > 0;
        $options    = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->trans((string) $responseMessage, ['%count%' => $entitiesCount]),
            $options
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param bool $status
     * @param int $iteration
     * @return mixed
     */
    protected function process($queryBuilder, $status, $iteration)
    {
        $result = $queryBuilder->getQuery()->iterate();
        foreach ($result as $entity) {
            /** @var RuleOwnerInterface $entity */
            $entity = $entity[0];

            $entity->getRule()->setEnabled($status);

            $this->entityManager->persist($entity);

            if (($iteration % self::FLUSH_BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $iteration++;
        }
        $this->entityManager->flush();

        return $iteration;
    }
}
