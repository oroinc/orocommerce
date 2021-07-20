<?php

namespace Oro\Bundle\PayPalBundle\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks whether express checkout name does not already used in base integration name.
 */
class UniqueExpressCheckoutNameValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_paypal.validator.unique_express_checkout_name_validator';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueExpressCheckoutName) {
            throw new UnexpectedTypeException($constraint, UniqueExpressCheckoutName::class);
        }

        if (!($value instanceof Channel)) {
            return;
        }

        if ($value->getTransport() instanceof PayPalSettings
            && !$this->validateExpressCheckoutName($value, $constraint)
        ) {
            return;
        }

        $this->validateIntegrationNameUniqueness($value, $constraint);
    }

    private function validateExpressCheckoutName(
        Channel $integration,
        UniqueExpressCheckoutName $constraint
    ): bool {
        /** @var PayPalSettings $transport */
        $transport = $integration->getTransport();

        if ($integration->getName() === $transport->getExpressCheckoutName()
            || $this->integrationNameAlreadyTaken($transport->getExpressCheckoutName())
        ) {
            $this->context->buildViolation($constraint->expressCheckoutNameMessage)->addViolation();

            return false;
        }

        return true;
    }

    private function integrationNameAlreadyTaken(string $expressCheckoutName): bool
    {
        return $this->getRepository(Channel::class)->findOneBy(['name' => $expressCheckoutName]) !== null;
    }

    private function getRepository(string $entityName): EntityRepository
    {
        return $this->doctrineHelper->getEntityRepository($entityName);
    }

    private function validateIntegrationNameUniqueness(
        Channel $integration,
        UniqueExpressCheckoutName $constraint
    ): void {
        $repository = $this->getRepository(PayPalSettings::class);

        if ($repository->findOneBy(['expressCheckoutName' => $integration->getName()])) {
            $this->context->buildViolation($this->translator->trans(
                $constraint->integrationNameUniquenessMessage,
                ['%name%' => $integration->getName()],
                'validators'
            ))->addViolation();
        }
    }
}
