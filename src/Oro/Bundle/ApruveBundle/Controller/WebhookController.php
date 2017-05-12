<?php

namespace Oro\Bundle\ApruveBundle\Controller;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\InvalidEventException;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\SourceTransactionNotFoundException;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\TransactionAlreadyExistsException;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/webhook")
 */
class WebhookController extends Controller
{
    const INVOICE_CLOSED_EVENT_NAME = 'invoice.closed';
    const PAYMENT_TERM_ACCEPTED_EVENT_NAME = 'payment_term.accepted';
    const ORDER_APPROVED_EVENT_NAME = 'order.approved';
    const ORDER_CANCELED_EVENT_NAME = 'order.canceled';

    /**
     * @Route("/notify/{token}", name="oro_apruve_webhook_notify", options={"expose"=true})
     * @Method("POST")
     *
     * @param string  $token
     * @param Request $request
     *
     * @return Response
     */
    public function notifyAction($token, Request $request)
    {
        $logger = $this->getLogger();

        $apruveSettings = $this->getApruveSettings($token);

        if ($apruveSettings === null) {
            $logger->error(sprintf('Invalid token: %s for Apruve webhook call', $token));
            return $this->createResponse('Access denied.', Response::HTTP_FORBIDDEN);
        }

        $paymentMethodIdentifier = $this->createPaymentMethodIdentifier($apruveSettings);

        $paymentMethod = $this->getPaymentMethod($paymentMethodIdentifier);

        if ($paymentMethod === null) {
            $logger->error(sprintf('Payment method with id: %s, is disabled.', $paymentMethodIdentifier));
            return $this->createResponse('Payment method is disabled.', Response::HTTP_NOT_FOUND);
        }

        $content = $request->getContent();

        $body = json_decode($content, true);

        if (!$body) {
            $message = 'Request body can\'t be decoded.';

            $logger->error(sprintf('%s. Content: %s', $message, $content));

            return $this->createResponse($message, Response::HTTP_BAD_REQUEST);
        }

        $eventName = $this->getNotifyEventName($body);

        $response = $this->createResponse();

        switch ($eventName) {
            case self::INVOICE_CLOSED_EVENT_NAME:
                $response = $this->handleInvoiceClosed($paymentMethod, $body);
                break;
            case self::PAYMENT_TERM_ACCEPTED_EVENT_NAME:
            case self::ORDER_APPROVED_EVENT_NAME:
            case self::ORDER_CANCELED_EVENT_NAME:
                // We don't need handle this events
                break;
            default:
                $logger->error(sprintf('Unknown apruve event: %s', $eventName));
                break;
        }

        return $response;
    }

    /**
     * @param PaymentMethodInterface $paymentMethod
     * @param array                  $body
     *
     * @return Response
     */
    private function handleInvoiceClosed(PaymentMethodInterface $paymentMethod, array $body)
    {
        $logger = $this->getLogger();

        $response = $this->createResponse();

        try {
            $this->get('oro_apruve.webhook_notify.event_handler.invoice_closed')->handle($paymentMethod, $body);
        } catch (InvalidEventException $exception) {
            $logger->error($exception->getMessage());
            $response = $this->createResponse('Invalid event body.', Response::HTTP_BAD_REQUEST);
        } catch (SourceTransactionNotFoundException $exception) {
            $logger->error($exception->getMessage());
            $response = $this->createResponse('Invoice was not found.', Response::HTTP_NOT_FOUND);
        } catch (TransactionAlreadyExistsException $exception) {
            $logger->error($exception->getMessage());
            $response = $this->createResponse('This event already handled.', Response::HTTP_CONFLICT);
        }

        return $response;
    }

    /**
     * @param string $content
     * @param int    $status
     *
     * @return Response
     */
    private function createResponse($content = '', $status = 200)
    {
        return new Response($content, $status);
    }

    /**
     * @param string $token
     *
     * @return ApruveSettings
     */
    private function getApruveSettings($token)
    {
        return $this->get('oro_apruve.repository.apruve_settings')->findOneBy([
            'apruveWebhookToken' => $token,
        ]);
    }

    /**
     * @param ApruveSettings $apruveSettings
     *
     * @return string
     */
    private function createPaymentMethodIdentifier(ApruveSettings $apruveSettings)
    {
        $identifierGenerator = $this->get('oro_apruve.method.generator.identifier');

        return $identifierGenerator->generateIdentifier($apruveSettings->getChannel());
    }

    /**
     * @param string $identifier
     *
     * @return null|PaymentMethodInterface
     */
    private function getPaymentMethod($identifier)
    {
        $apruveMethodProvider = $this->get('oro_apruve.method.apruve.provider');

        return $apruveMethodProvider->getPaymentMethod($identifier);
    }

    /**
     * @param array $body
     *
     * @return string|null
     */
    private function getNotifyEventName(array $body)
    {
        if (array_key_exists('event', $body)) {
            return $body['event'];
        }

        return null;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->get('logger');
    }
}
