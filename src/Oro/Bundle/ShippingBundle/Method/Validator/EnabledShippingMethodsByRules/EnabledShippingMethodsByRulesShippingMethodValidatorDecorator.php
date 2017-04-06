<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\EnabledShippingMethodsByRules;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var ShippingMethodTypeConfigRepository
     */
    private $methodTypeRepository;

    /**
     * @var ShippingMethodRegistry
     */
    private $methodRegistry;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShippingMethodValidatorInterface                                $parentShippingMethodValidator
     * @param Common\CommonShippingMethodValidatorResultErrorFactoryInterface $errorFactory
     * @param ShippingMethodTypeConfigRepository                              $methodTypeRepository
     * @param ShippingMethodRegistry                                          $methodRegistry
     * @param TranslatorInterface                                             $translator
     * @param LoggerInterface                                                 $logger
     */
    public function __construct(
        ShippingMethodValidatorInterface $parentShippingMethodValidator,
        Common\CommonShippingMethodValidatorResultErrorFactoryInterface $errorFactory,
        ShippingMethodTypeConfigRepository $methodTypeRepository,
        ShippingMethodRegistry $methodRegistry,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->parentShippingMethodValidator = $parentShippingMethodValidator;
        $this->errorFactory = $errorFactory;
        $this->methodTypeRepository = $methodTypeRepository;
        $this->methodRegistry = $methodRegistry;
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
            = $this->calculateNonDeletableShippingMethodTypeIdentifiers($shippingMethod);

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
            ['%types%' => implode(',', $nonDeletableShippingMethodTypeLabels)]
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
     * @param ShippingMethodInterface $shippingMethod
     *
     * @return string[]
     */
    private function calculateNonDeletableShippingMethodTypeIdentifiers(ShippingMethodInterface $shippingMethod)
    {
        $enabledTypes = $this->methodTypeRepository->findEnabledByMethodIdentifier(
            $shippingMethod->getIdentifier()
        );

        $shippingMethodTypeIdentifiers = array_map(
            function (ShippingMethodTypeInterface $value) {
                return $value->getIdentifier();
            },
            $shippingMethod->getTypes()
        );

        $enabledShippingMethodTypesIdentifiers = array_map(
            function (ShippingMethodTypeConfig $value) {
                return $value->getType();
            },
            $enabledTypes
        );

        $uniqueEnabledShippingMethodTypesIdentifiers = array_unique($enabledShippingMethodTypesIdentifiers);

        $nonDeletableShippingMethodTypes = array_diff(
            $uniqueEnabledShippingMethodTypesIdentifiers,
            $shippingMethodTypeIdentifiers
        );

        return $nonDeletableShippingMethodTypes;
    }

    /**
     * @param string   $methodIdentifier
     * @param string[] $methodTypeIdentifiers
     *
     * @return string[]
     */
    private function getShippingMethodTypesLabels($methodIdentifier, array $methodTypeIdentifiers)
    {
        $method = $this->methodRegistry->getShippingMethod($methodIdentifier);
        if (!$method) {
            $this->logger->error('Shipping method does not exist.', [
                'method_identifier' => $methodIdentifier,
            ]);

            return [];
        }

        $labels = [];
        foreach ($methodTypeIdentifiers as $methodTypeIdentifier) {
            $type = $method->getType($methodTypeIdentifier);
            if (!$method) {
                $this->logger->error('Shipping method type does not exist.', [
                    'method_identifier' => $methodIdentifier,
                    'method_type_identifier' => $methodTypeIdentifier,
                ]);

                return [];
            }
            $labels[] = $type->getLabel();
        }

        return $labels;
    }
}
