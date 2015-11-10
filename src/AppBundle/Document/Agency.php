<?php
/**
 * @file
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Agency
{
    /**
     * @MongoDB\id
     */
    protected $id;

    /**
     * @MongoDB\string
     */
    protected $agencyId;

    /**
     * @MongoDB\string
     */
    protected $key;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set agencyId
     *
     * @param string $agencyId
     * @return self
     */
    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;
        return $this;
    }

    /**
     * Get agencyId
     *
     * @return string $agencyId
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get key
     *
     * @return string $key
     */
    public function getKey()
    {
        return $this->key;
    }
}
