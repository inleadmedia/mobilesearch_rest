<?php

namespace AppBundle\Rest;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

/**
 * Class RestTaxonomyRequest
 *
 * TODO: Convert to a service.
 *
 * @deprecated Consider to move methods to a document repository instead.
 */
class RestTaxonomyRequest extends RestBaseRequest
{

    /**
     * RestTaxonomyRequest constructor.
     *
     * @param \Doctrine\Bundle\MongoDBBundle\ManagerRegistry $em
     */
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);
    }

    /**
     * {@inheritDoc}
     */
    protected function get($id, $agency)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function exists($id, $agency)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function insert()
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function update($id, $agency)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function delete($id, $agency)
    {
    }

    /**
     * @param $agency
     * @param $contentType
     *
     * @return array
     */
    public function fetchVocabularies($agency, $contentType)
    {
        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findBy([
                'agency' => $agency,
                'type' => $contentType,
            ]);

        $vocabularies = [];
        foreach ($content as $node) {
            if (!is_array($node->getTaxonomy())) {
                continue;
            }

            foreach ($node->getTaxonomy() as $vocabularyName => $vocabulary) {
                if (!empty($vocabulary['terms']) && is_array($vocabulary['terms'])) {
                    $vocabularies[$vocabularyName] = $vocabulary['name'];
                }
            }
        }

        return $vocabularies;
    }

    /**
     * @param $agency
     * @param $vocabulary
     * @param $contentType
     * @param $query
     *
     * @return array
     */
    public function fetchTermSuggestions($agency, $vocabulary, $contentType, $query)
    {
        $field = 'taxonomy.'.$vocabulary.'.terms';

        $result = $this->em
            ->getManager()
            ->createQueryBuilder('AppBundle:Content')
            ->field('agency')->equals($agency)
            ->field('type')->equals($contentType)
            ->where(
                'function() {
                    if (!this.taxonomy || !this.taxonomy.'.$vocabulary.') {
                        return false;
                    }

                    var iterator = function(data, value) {
                        var regex = new RegExp(value, "ig");

                        for (var field in data) {
                            if (field.match(regex)) {
                                return true;
                            }

                            if (typeof data[field] === "object") {
                                var found = false;
                                found = iterator(data[field], value);
                                if (found) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    }

                    return iterator(this.'.$field.' || [], "'.$query.'");
                }'
            )
            ->getQuery()->execute();

        $terms = [];

        // In the query above we only found the content entities that match
        // the query. Now, we actually search again within the result to
        // get the terms.

        // Recursive worker to find nested values.
        $worker = function (array $data, $value) use (&$worker, &$terms) {
            foreach ($data as $term => $children) {
                $pattern = '/'.$value.'/i';
                if (preg_match($pattern, $term)) {
                    $terms[] = $term;
                }

                // Check for array argument, so nested values not
                // being an array don't provoke fatal errors.
                if (!empty($children) && is_array($children)) {
                    $worker($children, $value);
                }
            }

            return $terms;
        };

        foreach ($result as $content) {
            $taxonomy = $content->getTaxonomy();
            if (isset($taxonomy[$vocabulary]) && is_array($taxonomy[$vocabulary]['terms'])) {
                $terms += $worker($taxonomy[$vocabulary]['terms'], $query);
            }
        }

        $terms = array_values(array_filter(array_unique($terms)));

        return $terms;
    }
}
