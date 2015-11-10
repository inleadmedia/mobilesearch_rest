<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Rest\RestBaseRequest;

class RestContent extends RestBaseRequest
{
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
