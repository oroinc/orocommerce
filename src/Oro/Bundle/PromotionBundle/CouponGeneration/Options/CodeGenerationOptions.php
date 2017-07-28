<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Options;

class CodeGenerationOptions
{
    const NUMERIC_CODE_TYPE = 'numeric';
    const ALPHANUMERIC_CODE_TYPE = 'alphanumeric';
    const ALPHABETIC_CODE_TYPE = 'alphabetic';

    /**
     * @var int
     */
    protected $codeLength = 12;

    /**
     * @var string
     */
    protected $codeType;

    /**
     * @var string
     */
    protected $codePrefix;

    /**
     * @var string
     */
    protected $codeSuffix;

    /**
     * @var int
     */
    protected $dashesSequence;

    /**
     * @return int
     */
    public function getCodeLength()
    {
        return $this->codeLength;
    }

    /**
     * @param int $codeLength
     */
    public function setCodeLength($codeLength)
    {
        $this->codeLength = $codeLength;
    }

    /**
     * @return string
     */
    public function getCodeType()
    {
        return $this->codeType;
    }

    /**
     * @param string $codeType
     */
    public function setCodeType($codeType)
    {
        $this->codeType = $codeType;
    }

    /**
     * @return string
     */
    public function getCodePrefix()
    {
        return $this->codePrefix;
    }

    /**
     * @param string $codePrefix
     */
    public function setCodePrefix($codePrefix)
    {
        $this->codePrefix = $codePrefix;
    }

    /**
     * @return string
     */
    public function getCodeSuffix()
    {
        return $this->codeSuffix;
    }

    /**
     * @param string $codeSuffix
     */
    public function setCodeSuffix($codeSuffix)
    {
        $this->codeSuffix = $codeSuffix;
    }

    /**
     * @return int
     */
    public function getDashesSequence()
    {
        return $this->dashesSequence;
    }

    /**
     * @param int $dashesSequence
     */
    public function setDashesSequence($dashesSequence)
    {
        $this->dashesSequence = $dashesSequence;
    }
}
