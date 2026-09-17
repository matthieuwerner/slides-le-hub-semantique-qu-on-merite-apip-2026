<?php

declare(strict_types=1);

namespace App\Observability;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Wires correlation identifiers and Server-Timing into the HTTP lifecycle.
 *
 * A listener rather than something inside the state processor, because these headers must be
 * present on *every* response, including validation failures and 500s. Those are exactly the
 * responses you most want a correlation id on, and they never reach a processor.
 */
final class TelemetryListener
{
    public function __construct(
        private readonly CorrelationId $correlationId,
        private readonly ServerTiming $serverTiming,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 512)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Reset at the *start* of each request rather than trusting the runtime to do it at the
        // end. Both services implement ResetInterface, which Symfony's services_resetter honours
        // in worker mode — but relying on that would make correctness depend on which runtime is
        // in use. Under a hand-rolled FrankenPHP worker loop, or if a request dies before
        // terminate(), a leaked correlation id from the previous request is the result. Resetting
        // here is cheap and unconditional.
        $this->correlationId->reset();
        $this->serverTiming->reset();

        $incoming = $event->getRequest()->headers->get(CorrelationId::HEADER);

        if (null !== $incoming && '' !== $incoming) {
            $this->correlationId->adopt($incoming);
        }
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -512)]
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        $response->headers->set(CorrelationId::HEADER, $this->correlationId->current());

        if (null !== $header = $this->serverTiming->header()) {
            $response->headers->set(ServerTiming::HEADER, $header);
        }
    }
}
