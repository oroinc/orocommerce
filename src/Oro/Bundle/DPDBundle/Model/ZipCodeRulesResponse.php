<?php

namespace Oro\Bundle\DPDBundle\Model;

class ZipCodeRulesResponse extends DPDResponse
{
    const DPD_ZIP_CODE_RULES_KEY = 'ZipCodeRules';
    const DPD_ZIP_CODE_RULES_COUNTRY_KEY = 'Country';
    const DPD_ZIP_CODE_RULES_ZIPCODE_KEY = 'ZipCode';
    const DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_KEY = 'NoPickupDays';
    const DPD_ZIP_CODE_RULES_EXPRESS_CUT_OFF_KEY = 'ExpressCutOff';
    const DPD_ZIP_CODE_RULES_CLASSIC_CUT_OFF_KEY = 'ClassicCutOff';
    const DPD_ZIP_CODE_RULES_PICKUP_DEPOT_KEY = 'PickupDepot';
    const DPD_ZIP_CODE_RULES_STATE_KEY = 'State';

    /**
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $zipCode;

    /**
     * @var array
     */
    protected $noPickupDays;

    /**
     * @var string
     */
    protected $expressCutOff;

    /**
     * @var string
     */
    protected $classicCutOff;

    /**
     * @var string
     */
    protected $pickupDepot;

    /**
     * @var string
     */
    protected $state;

    /**
     * @param array $values
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);
        $this->noPickupDays = [];
        if ($this->isSuccessful()) {
            if (!$this->values->offsetExists(self::DPD_ZIP_CODE_RULES_KEY)) {
                throw new \InvalidArgumentException('No ZipCodeRules parameter found in response data');
            }
            $zipCodeRules = $this->values->offsetGet(self::DPD_ZIP_CODE_RULES_KEY);

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_COUNTRY_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No Country parameter found in response data');
            }
            $this->country = $zipCodeRules[self::DPD_ZIP_CODE_RULES_COUNTRY_KEY];

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_ZIPCODE_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No ZipCode parameter found in response data');
            }
            $this->zipCode = $zipCodeRules[self::DPD_ZIP_CODE_RULES_ZIPCODE_KEY];

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No NoPickupDays parameter found in response data');
            }
            $noPickupDaysTmp = explode(',', $zipCodeRules[self::DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_KEY]);
            $this->noPickupDays = array_flip($noPickupDaysTmp);

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_EXPRESS_CUT_OFF_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No ExpressCutOff parameter found in response data');
            }
            $this->expressCutOff = $zipCodeRules[self::DPD_ZIP_CODE_RULES_EXPRESS_CUT_OFF_KEY];

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_CLASSIC_CUT_OFF_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No ClassicCutOff parameter found in response data');
            }
            $this->classicCutOff = $zipCodeRules[self::DPD_ZIP_CODE_RULES_CLASSIC_CUT_OFF_KEY];

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_PICKUP_DEPOT_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No PickupDepot parameter found in response data');
            }
            $this->pickupDepot = $zipCodeRules[self::DPD_ZIP_CODE_RULES_PICKUP_DEPOT_KEY];

            if (!array_key_exists(self::DPD_ZIP_CODE_RULES_STATE_KEY, $zipCodeRules)) {
                throw new \InvalidArgumentException('No State parameter found in response data');
            }
            $this->state = $zipCodeRules[self::DPD_ZIP_CODE_RULES_STATE_KEY];
        }
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @return array
     */
    public function getNoPickupDays()
    {
        return array_keys($this->noPickupDays);
    }

    /**
     * @param \DateTime $date
     *
     * @return bool
     */
    public function isNoPickupDay(\DateTime $date)
    {
        return array_key_exists($date->format('d.m.Y'), $this->noPickupDays);
    }

    /**
     * @return string
     */
    public function getExpressCutOff()
    {
        return $this->expressCutOff;
    }

    /**
     * @return string
     */
    public function getClassicCutOff()
    {
        return $this->classicCutOff;
    }

    /**
     * @return string
     */
    public function getPickupDepot()
    {
        return $this->pickupDepot;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
