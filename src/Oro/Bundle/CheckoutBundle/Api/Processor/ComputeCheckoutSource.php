<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Computes a value of "source" association for Checkout entity.
 */
class ComputeCheckoutSource implements ProcessorInterface
{
    private const string ASSOCIATION_NAME = 'source';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $dataFieldName = $context->getResultFieldName(self::ASSOCIATION_NAME);
        $associationData = null;
        if (isset($data[$dataFieldName]) && !$data[$dataFieldName]['deleted']) {
            $config = $context->getConfig();
            $associationPrefix = self::ASSOCIATION_NAME . ConfigUtil::PATH_DELIMITER;
            $associationPrefixLength = \strlen($associationPrefix);
            $dependsOn = $config->getField(self::ASSOCIATION_NAME)->getDependsOn();
            foreach ($dependsOn as $targetFieldPath) {
                if (!str_starts_with($targetFieldPath, $associationPrefix)) {
                    continue;
                }
                $targetFieldName = substr($targetFieldPath, $associationPrefixLength);
                if (null === $data[$dataFieldName][$targetFieldName]) {
                    continue;
                }

                $targetEntityField = $config->getField($dataFieldName)
                    ?->getTargetEntity()
                    ?->getField($targetFieldName);
                if (null === $targetEntityField) {
                    continue;
                }
                $targetEntityClass = $targetEntityField->getTargetClass();
                if (!$targetEntityClass || !is_a($targetEntityClass, CheckoutSourceEntityInterface::class, true)) {
                    continue;
                }

                $associationData = $data[$dataFieldName][$targetFieldName];
                $associationData[ConfigUtil::CLASS_NAME] = $targetEntityClass;
            }
        }
        $data[self::ASSOCIATION_NAME] = $associationData;
        $context->setData($data);
    }
}
