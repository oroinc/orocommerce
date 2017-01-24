<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RatesCsvFileValidator extends ConstraintValidator
{
    const ALIAS = 'oro_dpd_rates_csv_file_validator';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof File) {
            $shippingServiceCodes = $this
                ->doctrineHelper
                ->getEntityRepository('OroDPDBundle:ShippingService')
                ->getAllShippingServiceCodes();
            /** @var Country[] $countries */
            $countries = $this
                ->doctrineHelper
                ->getEntityRepository('OroAddressBundle:Country')
                ->createQueryBuilder('country', 'country.iso2Code')
                ->getQuery()
                ->getResult();

            $handle = fopen($value->getRealPath(), 'rb');
            $rowCounter = 0;

            // FIXME: Use translations for error messages
            // FIXME: Use error specific messages
            while (($row = fgetcsv($handle, 1000)) !== false) {
                ++$rowCounter;
                if ($rowCounter === 1) {
                    continue;
                }
                if (count($row) < 5) {
                    $this->context->addViolation($constraint->message, ['{{ row_count }}' => $rowCounter]);
                    continue;
                }
                list($shippingServiceCode, $countryCode, $regionCode, $weightValue, $priceValue) = $row;

                // shippingService not set or unknown
                if (empty($shippingServiceCode) || !array_key_exists($shippingServiceCode, $shippingServiceCodes)) {
                    $this->context->addViolation($constraint->message, ['{{ row_count }}' => $rowCounter]);
                    continue;
                }
                // country not set or unknown
                if (empty($countryCode) || !array_key_exists($countryCode, $countries)) {
                    $this->context->addViolation($constraint->message, ['{{ row_count }}' => $rowCounter]);
                    continue;
                }
                // region unknown
                if (!empty($regionCode)
                    && !$countries[$countryCode]->getRegions()->exists(
                        function ($key, $element) use ($regionCode) {
                            /* @var Region $element */
                            return $element->getCombinedCode() === $regionCode;
                        }
                    )
                ) {
                    $this->context->addViolation($constraint->message, ['{{ row_count }}' => $rowCounter]);
                    continue;
                }
                if (!empty($weightValue) && !is_numeric($weightValue)) {
                    $this->context->addViolation($constraint->message, ['{{ row_count }}' => $rowCounter]);
                    continue;
                }
                // price value not set or not a number
                if (empty($priceValue) || !is_numeric($priceValue)) {
                    $this->context->addViolation($constraint->message, ['{{ row_count }}' => $rowCounter]);
                    continue;
                }
            }
            fclose($handle);
        }
    }
}
