<?php

namespace Oro\Bundle\ApruveBundle\Services;

use Oro\Bundle\ApruveBundle\Provider\ApruvePublicKeyProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SignatureVerifyingService implements SignatureVerifyingServiceInterface
{
    const SIGNATURE_HEADER_NAME = 'X-Apruve-Signature';

    /**
     * @internal
     */
    const SIGNATURE_ALGORITHM = OPENSSL_ALGO_SHA256;

    /**
     * @var ApruvePublicKeyProviderInterface
     */
    private $apruvePublicKeyProvider;

    /**
     * @param ApruvePublicKeyProviderInterface $apruvePublicKeyProvider
     */
    public function __construct(ApruvePublicKeyProviderInterface $apruvePublicKeyProvider)
    {
        $this->apruvePublicKeyProvider = $apruvePublicKeyProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function verifyRequestSignature(Request $request)
    {
        $decodedSignuture = $request->headers->get(self::SIGNATURE_HEADER_NAME);

        $signature = base64_decode($decodedSignuture);

        $result = openssl_verify(
            $request->getContent(),
            $signature,
            $this->apruvePublicKeyProvider->getPublicKey(),
            self::SIGNATURE_ALGORITHM
        );

        return $result === 1;
    }
}
