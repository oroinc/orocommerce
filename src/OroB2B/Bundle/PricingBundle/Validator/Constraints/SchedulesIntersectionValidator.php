<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class SchedulesIntersectionValidator extends ConstraintValidator
{

    /**
     * @param PriceList $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PriceList) {
            return;
        }

        $schedules = $value->getSchedules();
        $intersections = [];

        foreach ($schedules as $index => $schedule) {
            if ($this->hasIntersection($schedules, $schedule)) {
                $intersections[] = $index;
            }
        }

        if ($intersections) {
            $this->context->buildViolation($constraint->message, [])
                ->addViolation();
        }
    }

    /**
     * @param ArrayCollection|PriceListSchedule[] $collection
     * @param PriceListSchedule $schedule
     * @return bool
     */
    protected function hasIntersection(ArrayCollection $collection, PriceListSchedule $schedule)
    {
        foreach ($collection as $item) {
            if ($item === $schedule) {
                continue;
            }

            $aLeft = $schedule->getActiveAt();
            $aRight = $schedule->getDeactivateAt();

            $bLeft = $item->getActiveAt();
            $bRight = $item->getDeactivateAt();

            if ((is_null($aLeft) || $aLeft <= $bRight) && (is_null($bRight) || $aRight >= $bLeft)) {
                return true;
            }
        }

        return false;
    }
}
