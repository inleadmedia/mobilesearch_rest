<?php

namespace MobileSearch\v2\RestBundle\Controller;

/**
 * Trait PaginatedResourcesTrait.
 */
trait PaginatedResourcesTrait
{

    /**
     *  Generates resource pagination links.
     *
     * @param int $amount Amount of results in response.
     * @param int $skip   Results collection offset.
     * @param int $hits   Total number of results available.
     *
     * @return array
     */
    public function generatePaginationLinks(int $amount, int $skip, int $hits)
    {
        return [
            'pagination' => [
                'amount' => $amount,
                'skip' => $skip,
                'hits' => $hits,
            ],
        ];
    }
}
