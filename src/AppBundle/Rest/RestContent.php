<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Rest\RestRequest;

class RestContent extends RestRequest
{
    public function __construct($requestBody)
    {
        parent::__construct($requestBody);
    }

    /**
     *
     * {@inheritDoc}
     *
     * @see \AppBundle\Rest\RestRequest::validateRequest()
     */
    public function validateRequest()
    {
        $isValid = parent::validateRequest();

        return $isValid;
    }
}
