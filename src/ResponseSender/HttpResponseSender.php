<?php

declare(strict_types=1);

namespace Laminas\Mvc\ResponseSender;

use Laminas\Http\Response;

class HttpResponseSender extends AbstractResponseSender
{
    /**
     * Send content
     */
    public function sendContent(SendResponseEvent $event): static
    {
        if ($event->contentSent()) {
            return $this;
        }
        $response = $event->getResponse();
        echo $response->getContent();
        $event->setContentSent();
        return $this;
    }

    /**
     * Send HTTP response
     */
    public function __invoke(SendResponseEvent $event): static
    {
        $response = $event->getResponse();
        if (! $response instanceof Response) {
            return $this;
        }

        $this->sendHeaders($event)
             ->sendContent($event);
        $event->stopPropagation(true);
        return $this;
    }
}
