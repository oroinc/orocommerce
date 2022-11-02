<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\EnabledShippingMethodsByRules;

use Oro\Bundle\ShippingBundle\Method\Exception\InvalidArgumentException;
use Oro\Bundle\ShippingBundle\Method\Provider\Label\Type\MethodTypeLabelsProviderInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Type\NonDeletable\NonDeletableMethodTypeIdentifiersProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnabledShippingMethodsByRulesShippingMethodValidatorDecorator implements ShippingMethodValidatorInterface
{
    const USED_SHIPPING_METHODS_ERROR = 'oro.shipping.method_type.used.error';

    /**
     * @var ShippingMethodValidatorInterface
     */
    private $parentShippingMethodValidator;

    /**
     * @var Common\CommonShippingMethodValidatorResultErrorFactoryInterface
     */
    private $errorFactory;

    /**
     * @var NonDeletableMethodTypeIdentifiersProviderInterface
     */
    private $nonDeletableTypeIdentifiersProvider;

    /**
     * @var MethodTypeLabelsProviderInterface
     */
    private $methodTypeLabelsProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ShippingMethodValidatorInterface $parentShippingMethodValidator,
        Common\CommonShippingMethodValidatorResultErrorFactoryInterface $errorFactory,
        NonDeletableMethodTypeIdentifiersProviderInterface $nonDeletableTypeIdentifiersProvider,
        MethodTypeLabelsProviderInterface $methodTypeLabelsProvider,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->parentShippingMethodValidator = $parentShippingMethodValidator;
        $this->errorFactory = $errorFactory;
        $this->nonDeletableTypeIdentifiersProvider = $nonDeletableTypeIdentifiersProvider;
        $this->methodTypeLabelsProvider = $methodTypeLabelsProvider;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ShippingMethodInterface $shippingMethod)
    {
        $result = $this->parentShippingMethodValidator->validate($shippingMethod);

        $nonDeletableShippingMethodTypeIdentifiers
            = $this->nonDeletableTypeIdentifiersProvider->getMethodTypeIdentifiers($shippingMethod);

        if ([] === $nonDeletableShippingMethodTypeIdentifiers) {
            return $result;
        }

        $nonDeletableShippingMethodTypeLabels = $this->getShippingMethodTypesLabels(
            $shippingMethod->getIdentifier(),
            $nonDeletableShippingMethodTypeIdentifiers
        );

        if ([] === $nonDeletableShippingMethodTypeLabels) {
            return $result;
        }

        $errorMessage = $this->translator->trans(
            self::USED_SHIPPING_METHODS_ERROR,
            ['%types%' => implode(', ', $nonDeletableShippingMethodTypeLabels)]
        );

        $errorsBuilder = $result->getErrors()
            ->createCommonBuilder()
            ->cloneAndBuild($result->getErrors())
            ->addError(
                $this->errorFactory->createError($errorMessage)
            );

        return $result->createCommonFactory()->createErrorResult($errorsBuilder->getCollection());
    }

    /**
     * @param string   $methodIdentifier
     * @param string[] $methodTypeIdentifiers
     *
     * @return string[]
     */
    private function getShippingMethodTypesLabels($methodIdentifier, array $methodTypeIdentifiers)
    {
        try {
            return $this->methodTypeLabelsProvider->getLabels($methodIdentifier, $methodTypeIdentifiers);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage(), [
                'method_identifier' => $methodIdentifier,
                'type_identifiers' => $methodTypeIdentifiers,
            ]);

            return [];
        }
    }
}
