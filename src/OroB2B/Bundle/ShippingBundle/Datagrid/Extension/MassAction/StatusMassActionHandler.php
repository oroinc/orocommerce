<?php

namespace Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\DependencyInjection\ServiceLink;

class StatusMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $responseMessage = 'oro.shipping.datagrid.status.success_message';

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        ServiceLink $securityFacadeLink
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->securityFacade = $securityFacadeLink->getService();
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
            $iteration = $this->handleShippingRuleStatuses($options, $data);
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
    protected function handleShippingRuleStatuses($options, $data)
    {
        $status = $options['enable'];
        $isAllSelected = $this->isAllSelected($data);
        $iteration = 0;
        $shippingRuleIds = [];

        if (array_key_exists('values', $data) && !empty($data['values'])) {
            $shippingRuleIds = explode(',', $data['values']);
        }

        if ($shippingRuleIds || $isAllSelected) {
            $queryBuilder = $this
                ->entityManager
                ->getRepository('OroShippingBundle:ShippingRule')
                ->createQueryBuilder('rule');


            if (!$isAllSelected) {
                $queryBuilder->andWhere($queryBuilder->expr()->in('rule.id', $shippingRuleIds));
            } elseif ($shippingRuleIds) {
                $queryBuilder->andWhere($queryBuilder->expr()->notIn('rule.id', $shippingRuleIds));
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
            $this->translator->transChoice(
                $responseMessage,
                $entitiesCount,
                ['%count%' => $entitiesCount]
            ),
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
            /** @var ShippingRule $entity */
            $entity = $entity[0];

            $entity->setEnabled($status);

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
