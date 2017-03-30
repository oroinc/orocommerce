<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\EnabledShippingMethodsByRules;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EnabledShippingMethodsByRulesShippingMethodValidator implements ShippingMethodValidatorInterface
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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ShippingMethodValidatorInterface                                $parentShippingMethodValidator
     * @param Common\CommonShippingMethodValidatorResultErrorFactoryInterface $errorFactory
     * @param ShippingMethodTypeConfigRepository                              $methodTypeRepository
     * @param TranslatorInterface                                             $translator
     */
    public function __construct(
        ShippingMethodValidatorInterface $parentShippingMethodValidator,
        Common\CommonShippingMethodValidatorResultErrorFactoryInterface $errorFactory,
        ShippingMethodTypeConfigRepository $methodTypeRepository,
        TranslatorInterface $translator
    ) {
        $this->parentShippingMethodValidator = $parentShippingMethodValidator;
        $this->errorFactory = $errorFactory;
        $this->methodTypeRepository = $methodTypeRepository;
        $this->translator = $translator;
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

        $errorMessage = $this->translator->trans(
            self::USED_SHIPPING_METHODS_ERROR,
            ['%types%' => implode(',', $nonDeletableShippingMethodTypeIdentifiers)]
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
}
