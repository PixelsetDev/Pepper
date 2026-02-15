<?php

namespace Pepper\Processes;

use Starlight\HTTP\Response;
use starlight\HTTP\Types\ResponseCode;

/**
 * Handles the responses for the API so that everything is kept uniform
 */
class PepperResponse {

    /**
     * Returns a response string.
     * @param ResponseCode $response - The HTTP response code.
     * @param mixed $data - The data to return in the response.
     * @param string|null $message - An optional status message with more information about why something happened.
     * @return string
     */
    public function api(ResponseCode $response, mixed $data = null, string|null $message = null): string
    {
        if ($data === null) { $data = 'null'; }
        if ($message === null) { $message = 'null'; } else { $message = '"'.htmlspecialchars($message).'"'; }

        new Response()->code($response);
        return '{"data": '.$data.', "message": '.$message.'}';
    }
}
