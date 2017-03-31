<?php

namespace Oro\Bundle\ApruveBundle\TokenGenerator;

interface TokenGeneratorInterface
{
    /**
     * Generates a token.
     *
     * @return string
     */
    public function generateToken();
}
