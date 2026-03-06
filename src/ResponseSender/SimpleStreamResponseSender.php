<?php

declare(strict_types=1);

namespace Laminas\Mvc\ResponseSender;

use function fpassthru;

use Laminas\Http\Response\Stream;

class SimpleStreamResponseSender extends AbstractResponseSender
{
    /**
     * Send the stream
     *
     * @return SimpleStreamResponseSender
     */
    public function sendStream(SendResponseEvent $event)
    {
        if ($event->contentSent()) {
            return $this;
        }
        $response = $event->getResponse();
        $stream   = $response->getStream();
        fpassthru($stream);
        $event->setContentSent();
    }

    /**
     * Send stream response
     */
    public function __invoke(SendResponseEvent $event): static
    {
        $response = $event->getResponse();
        if (! $response instanceof Stream) {
            return $this;
        }

        $this->sendHeaders($event);
        $this->sendStream($event);
        $event->stopPropagation(true);
        return $this;
    }
}
