<?php

namespace MobileSearch\v2\RestBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait PaginatedResourcesTrait.
 */
trait NavigatedResourcesTrait
{

    /**
     *  Generates resource pagination links.
     *
     * @param string  $uri    Resource uri.
     * @param int     $amount Amount of results in response.
     * @param int     $skip   Results collection offset.
     * @param int     $hits   Total number of results available.
     *
     * @return array
     */
    public function generateNavigationLinks(string $uri, int $amount, int $skip, int $hits)
    {
        // Add the 'skip' parameter, if it is not already in the uri.
        $skipPattern = '/(?<=\?|&)skip=\d+(?=$|&)/';
        if (!preg_match($skipPattern, $uri, $matches)) {
            $uri .= "&skip=$skip";
        }

        // Add the 'amount' parameter, if it is not already in the uri.
        $amountPattern = '/(?<=\?|&)amount=\d+(?=$|&)/';
        if (!preg_match($amountPattern, $uri, $matches)) {
            $uri .= "&amount=$amount";
        }

        $nextLink = $prevLink = '';

        // No reason to create a prev link when we are out of bounds of possible
        // results. For instance when already at first page or skipping way
        // beyond available number of results.
        if ($skip - $amount >= 0 && $skip <= $hits) {
            $prevSkip = $skip - $amount;
            $prevLink = preg_replace($skipPattern, 'skip=' . $prevSkip, $uri);
        }

        // Likewise, no reason to have a next link when we reached or crossed
        // the bounds of available number of results.
        if ($skip + $amount < $hits) {
            $nextSkip = $skip + $amount > $hits ? $hits - $skip : $skip + $amount;
            $nextLink = preg_replace($skipPattern, 'skip=' . $nextSkip, $uri);
        }

        return [
            'navigation' => [
                'prev' => $prevLink,
                'next' => $nextLink,
            ],
        ];
    }
}
