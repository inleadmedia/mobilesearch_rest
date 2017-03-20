<?php

/**
 * @file
 */
namespace AppBundle\Rest;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;
use AppBundle\Rest\RestBaseRequest;

class RestTaxonomyRequest extends RestBaseRequest
{

    public function __construct(MongoEM $em)
    {
        parent::__construct($em);
    }

    protected function get($id, $agency)
    {}

    protected function exists($id, $agency)
    {}

    protected function insert()
    {}

    protected function update($id, $agency)
    {}

    protected function delete($id, $agency)
    {}

    public function fetchVocabularies($agency, $contentType)
    {
        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findBy(array(
                'agency' => $agency,
                'type' => $contentType
            ));

        $vocabularies = array();
        foreach ($content as $node) {
            foreach ($node->getTaxonomy() as $vocabularyName => $vocabulary) {
                if (!empty($vocabulary['terms']) && is_array($vocabulary['terms'])) {
                    $vocabularies[$vocabularyName] = $vocabulary['name'];
//                     foreach ($vocabulary['terms'] as $term) {
//                         $vocabularies[$vocabularyName]['terms'][] = $term;
//                     }

//                     $vocabularies[$vocabularyName]['terms'] = array_values(array_unique($vocabularies[$vocabularyName]['terms']));
                }
            }
        }

        return $vocabularies;
    }

    public function fetchTermSuggestions($agency, $vocabulary, $contentType, $query)
    {
        $field = 'taxonomy.' . $vocabulary . '.terms';
        $pattern = '/' . $query . '/i';

        $result = $this->em->getRepository('AppBundle:Content')->findBy(array(
            'agency' => $agency,
            'type' => $contentType,
            $field => array('$in' => array(new \MongoRegex($pattern)))
        ));

        $terms = array();
        foreach ($result as $content) {
            $taxonomy = $content->getTaxonomy();
            if (isset($taxonomy[$vocabulary]) && is_array($taxonomy[$vocabulary]['terms'])) {
                foreach ($taxonomy[$vocabulary]['terms'] as $term) {
                    $pattern = '/' . $query . '/i';
                    if (preg_match($pattern, $term)) {
                        $terms[] = $term;
                    }
                }
            }
        }

        $terms = array_values(array_unique($terms));

        return $terms;
    }

    public function fetchRelatedContent($agency, array $vocabulary, array $terms, $sort = '', $dir = 'DESC', $amount = 10, $skip = 0)
    {
        if (count($vocabulary) != count($terms)) {
            throw new \Exception('Number of vocabulary and terms count mismatch.');
        }

        $criteria = array(
            'agency' => $agency,
        );

        foreach ($vocabulary as $k => $item) {
            $field = 'taxonomy.' . $item . '.terms';
            $criteria[$field] = array('$in' => explode(',', $terms[$k]));
        }

        $order = array();
        if ($sort && $dir) {
            $order = array(
              $sort => $dir,
            );
        }

        $content = $this->em
          ->getRepository('AppBundle:Content')
          ->findBy($criteria, $order, (int) $amount, (int) $skip);

        return $content;
    }
}
