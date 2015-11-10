<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

interface RestRequestValidateInterface
{
    public function validateRequest();
}

abstract class RestBaseRequest implements RestRequestValidateInterface
{
    protected $agencyId = NULL;
    protected $signature = NULL;
    protected $requestBody = NULL;
    protected $lastMessage = '';
    protected $em = NULL;

    public function __construct(MongoEM $em)
    {
        $this->em = $em;
    }

    public function setRequestBody($requestBody)
    {
        $this->requestBody = json_decode($requestBody, TRUE);
        $this->agencyId = !empty($this->requestBody['credentials']['agencyId']) ? $this->requestBody['credentials']['agencyId'] : NULL;
        $this->signature = !empty($this->requestBody['credentials']['key']) ? $this->requestBody['credentials']['key'] : NULL;
    }

    public function validateRequest()
    {
        $status = TRUE;

        if (!$this->requestBody)
        {
            $this->lastMessage = 'Failed parsing request body.';
            $status = FALSE;
        }
        elseif (empty($this->requestBody['credentials']))
        {
            $this->lastMessage = 'Failed validating request. Check your credentials.';
            $status = FALSE;
        }
        elseif (!$this->validateAgency())
        {
            $this->lastMessage = 'Failed validating request. Check your agency.';
            $status = FALSE;
        }
        elseif (!$this->validateSignature())
        {
            $this->lastMessage = 'Failed validating request. Check your key.';
            $status = FALSE;
        }

        return $status;
    }

    private function validateAgency()
    {
        $agencyIsValid = $this->isAgencyValid($this->agencyId);

        return $agencyIsValid;
    }

    private function isAgencyValid($agencyId)
    {
        $agency = $this->getAgencyById($agencyId);

        return !is_null($agency);
    }

    public function getAgencyById($agencyId)
    {
        $agency = $this->em
            ->getRepository('AppBundle:Agency')
            ->findOneBy(array('agencyId' => $agencyId));

        return $agency;
    }

    private function validateSignature()
    {
        $keyIsValid = $this->isSignatureValid($this->agencyId, $this->signature);

        return $keyIsValid;
    }

    private function isSignatureValid($agencyId, $signature)
    {
        $agency = $this->getAgencyById($agencyId);

        if ($agency) {
            $key = $agency->getKey();
            $targetSignature = sha1($agencyId . $key);

            if ($signature == $targetSignature) {
                return TRUE;
            }
        }

        return FALSE;
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
