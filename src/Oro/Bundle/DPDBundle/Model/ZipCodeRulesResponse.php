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

    const DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_FORMAT = 'd.m.Y';

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
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    public function parse(array $data)
    {
        parent::parse($data);
        $this->noPickupDays = [];
        if ($this->isSuccessful()) {
            $zipCodeRules = $this->getKeyFromArray(
                $data,
                self::DPD_ZIP_CODE_RULES_KEY,
                'No ZipCodeRules parameter found in response data'
            );

            $this->country = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_COUNTRY_KEY,
                'No Country parameter found in response data'
            );

            $this->zipCode = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_ZIPCODE_KEY,
                'No ZipCode parameter found in response data'
            );

            $noPickupDaysAsString = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_KEY,
                'No NoPickupDays parameter found in response data'
            );
            $noPickupDays = explode(',', $noPickupDaysAsString);
            $this->noPickupDays = array_flip($noPickupDays);

            $this->expressCutOff = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_EXPRESS_CUT_OFF_KEY,
                'No ExpressCutOff parameter found in response data'
            );

            $this->classicCutOff = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_CLASSIC_CUT_OFF_KEY,
                'No ClassicCutOff parameter found in response data'
            );

            $this->pickupDepot = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_PICKUP_DEPOT_KEY,
                'No PickupDepot parameter found in response data'
            );

            $this->state = $this->getKeyFromArray(
                $zipCodeRules,
                self::DPD_ZIP_CODE_RULES_STATE_KEY,
                'No State parameter found in response data'
            );
        }
    }

    /**
     * @param array  $data
     * @param string $key
     * @param string $errorIfKeyMissed
     *
     * @return mixed
     */
    private function getKeyFromArray(array $data, $key, $errorIfKeyMissed)
    {
        if (!array_key_exists($key, $data)) {
            throw new \InvalidArgumentException($errorIfKeyMissed);
        }

        return $data[$key];
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
        return array_map(
            function ($noPickupDayString) {
                return \DateTime::createFromFormat(
                    self::DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_FORMAT.'|',
                    $noPickupDayString
                );
            },
            array_keys($this->noPickupDays)
        );
    }

    /**
     * @param \DateTime $date
     *
     * @return bool
     */
    public function isNoPickupDay(\DateTime $date)
    {
        return array_key_exists(
            $date->format(self::DPD_ZIP_CODE_RULES_NO_PICKUP_DAYS_FORMAT),
            $this->noPickupDays
        );
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

    /**
     * @return array
     */
    public function toArray()
    {
        $response = parent::toArray();
        $response = array_merge(
            $response,
            [
                'Country' => $this->getCountry(),
                'ZipCode' => $this->getZipCode(),
                'NoPickupDays' => $this->getNoPickupDays(),
                'ClassicCutOff' => $this->getClassicCutOff(),
                'ExpressCutOff' => $this->getExpressCutOff(),
                'PickupDepot' => $this->getPickupDepot(),
                'State' => $this->getState(),
            ]
        );

        return $response;
    }
}
