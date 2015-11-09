<?php
/**
 * @file
 */

namespace AppBundle\Rest;

interface RestRequestValidateInterface
{
    public function validateRequest();
}

abstract class RestRequest implements RestRequestValidateInterface
{
    protected $requestBody;
    protected $lastMessage;

    public function __construct($requestBody) {
        $this->requestBody = json_decode($requestBody, TRUE);
    }

    public function validateRequest()
    {
        $status = TRUE;

        if (!$this->requestBody) {
            $this->lastMessage = 'Failed parsing request body.';
            $status = FALSE;
        }
        elseif (empty($this->requestBody['credentials'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials.';
            $status = FALSE;
        }
        elseif (!$this->validateCredentials()) {
            $this->lastMessage = 'Failed validating request. Check your agency and/or key.';
            $status = FALSE;
        }

        return $status;
    }

    private function validateCredentials()
    {
        $agencyIsNotEmpty = !empty($this->requestBody['credentials']['agencyId']);
        $keyIsNotEmpty = !empty($this->requestBody['credentials']['key']);

        return $agencyIsNotEmpty && $keyIsNotEmpty;
    }

    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    public function getParsedBody()
    {
        return $this->requestBody;
    }
}
