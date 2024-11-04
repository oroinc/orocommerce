<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prevents getting and setting order status value and switches to the internal order status
 * when "Enable External Status Management" configuration option is disabled.
 */
class ConfigureOrderStatusField implements ProcessorInterface
{
    private OrderConfigurationProviderInterface $configurationProvider;
    private DoctrineHelper $doctrineHelper;

    public function __construct(
        OrderConfigurationProviderInterface $configurationProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            return;
        }

        $statusField = $context->getResult()->findField('status', true);
        if (null === $statusField) {
            return;
        }

        if ($context->getRequestType()->contains('frontend')) {
            $statusField->setPropertyPath('internal_status');
            $statusField->setAssociationQuery(
                $this->doctrineHelper->createQueryBuilder(EnumOption::class, 'r')
                    ->innerJoin(
                        Order::class,
                        'e',
                        Join::WITH,
                        "JSON_EXTRACT(e.serialized_data, 'internal_status') = r.id"
                    )
            );
            $context->getResult()->findField('internal_status', true)
                ?->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        } else {
            $statusField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
            $statusField->setAssociationQuery(null);
        }
        switch ($context->getTargetAction()) {
            case ApiAction::CREATE:
            case ApiAction::UPDATE:
            case ApiAction::UPDATE_RELATIONSHIP:
                $statusFieldFormOptions = $statusField->getFormOptions();
                $statusFieldFormOptions['mapped'] = false;
                $statusField->setFormOptions($statusFieldFormOptions);
                break;
        }
    }
}
