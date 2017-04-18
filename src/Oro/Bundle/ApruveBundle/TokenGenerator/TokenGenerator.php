<?php

namespace Oro\Bundle\ApruveBundle\TokenGenerator;

class TokenGenerator implements TokenGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generateToken()
    {
        // Generate an URI safe base64 encoded string.
        $bytes = random_bytes(36);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
