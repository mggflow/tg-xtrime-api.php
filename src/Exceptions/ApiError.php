<?php

namespace MGGFLOW\Telegram\Xtrime\Exceptions;

class ApiError extends \Exception
{
    protected $message = 'Some API error has occurred.';

    public function fillMessageFromResponseErrors(object $response): self {
        if (!empty($response->errors)){
            $messages = array_map(function ($respError) {
                return $respError->message;
            }, $response->errors);

            $this->message = join(' & ', $messages);
        }

        return $this;
    }
}