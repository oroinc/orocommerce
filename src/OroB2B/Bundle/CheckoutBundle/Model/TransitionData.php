<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class TransitionData
{
    /**
     * @var Transition
     */
    protected $transition;

    /**
     * @var bool
     */
    protected $allowed = true;

    /**
     * @var ArrayCollection
     */
    protected $errors;

    /**
     * @param Transition $transition
     * @param bool $allowed
     * @param ArrayCollection $errors
     */
    public function __construct(Transition $transition, $allowed, ArrayCollection $errors)
    {
        $this->transition = $transition;
        $this->allowed = $allowed;
        $this->errors = $errors;
    }

    /**
     * @return boolean
     */
    public function isAllowed()
    {
        return $this->allowed;
    }

    /**
     * @return ArrayCollection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return Transition
     */
    public function getTransition()
    {
        return $this->transition;
    }
}
