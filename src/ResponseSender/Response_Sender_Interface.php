<?php

declare (strict_types=1);
namespace Laminas\Mvc\Response_Sender;

interface Response_Sender_Interface
{
    /**
     * Send the response
     *
     * @return void
     */
    public function __invoke(Send_Response_Event $event);
}