<?php

namespace Pepper\Processes;

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
    public function api(ResponseCode $response, mixed $data, string|null $message = null): string
    {
        return '{"status": {"code": "'.$response->code.' '.$response->message.'", "message": "'.$message.'"}, "data": '.$data.'}';
    }
}
