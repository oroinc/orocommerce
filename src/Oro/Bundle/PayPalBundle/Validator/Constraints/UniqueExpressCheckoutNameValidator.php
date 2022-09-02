<?php

namespace Oro\Bundle\PayPalBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether express checkout name does not already used in base integration name.
 */
class UniqueExpressCheckoutNameValidator extends ConstraintValidator
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        return $this->doctrine->getRepository(Channel::class)->findOneBy(['name' => $expressCheckoutName]) !== null;
    }

    private function validateIntegrationNameUniqueness(
        Channel $integration,
        UniqueExpressCheckoutName $constraint
    ): void {
        $repository = $this->doctrine->getRepository(PayPalSettings::class);
        if ($repository->findOneBy(['expressCheckoutName' => $integration->getName()])) {
            $this->context->addViolation(
                $constraint->integrationNameUniquenessMessage,
                ['%name%' => $integration->getName()]
            );
        }
    }
}
