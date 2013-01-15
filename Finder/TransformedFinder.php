<?php

namespace FOQ\ElasticaBundle\Finder;

use FOQ\ElasticaBundle\Finder\FinderInterface;
use FOQ\ElasticaBundle\Finder\PaginatedFinderInterface;
use FOQ\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use FOQ\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use FOQ\ElasticaBundle\Paginator\FantaPaginatorAdapter;
use Pagerfanta\Pagerfanta;
use Elastica_Searchable;
use Elastica_Query;
use Elastica_ResultSet;

/**
 * Finds elastica documents and map them to persisted objects
 */
class TransformedFinder implements PaginatedFinderInterface
{
    protected $searchable;
    protected $transformer;

    public function __construct(Elastica_Searchable $searchable, ElasticaToModelTransformerInterface $transformer)
    {
        $this->searchable  = $searchable;
        $this->transformer = $transformer;
    }

    /**
     * Search for a query string
     *
     * @param string $query
     * @param integer $limit
     * @return array of model objects
     **/
    public function find($query, $limit = null)
    {
        $results = $this->search($query, $limit);

        return $this->transformResultSet($results);
    }

    public function findHybrid($query, $limit = null)
    {
        $results = $this->search($query, $limit);

        return $this->hybridTransformResultSet($results);
    }

    /**
     * Search for a query and return full result set.
     * 
     * @param string $query
     * @param integer $limit
     * @return Elastica_ResultSet
     */
    public function findResultSet($query, $limit = null)
    {
        $results = $this->search($query, $limit);

        return $results;
    }

    /**
     * Transforms Elastica_ResultSet into an array of model objects.
     * 
     * @param Elastica_ResultSet $resultSet
     * @return array of model objects
     */
    public function transformResultSet(Elastica_ResultSet $resultSet)
    {
        $results = $resultSet->getResults();
        
        return $this->transformer->transform($results);
    }

    public function hybridTransformResultSet(Elastica_ResultSet $resultSet)
    {
        $results = $resultSet->getResults();

        return $this->transformer->hybridTransform($results);
    }

    protected function search($query, $limit = null)
    {
        $queryObject = Elastica_Query::create($query);
        if (null !== $limit) {
            $queryObject->setLimit($limit);
        }
        $results = $this->searchable->search($queryObject);

        return $results;
    }


    /**
     * Gets a paginator wrapping the result of a search
     *
     * @return Pagerfanta
     */
    public function findPaginated($query)
    {
        $queryObject = Elastica_Query::create($query);
        $paginatorAdapter = $this->createPaginatorAdapter($queryObject);

        return new Pagerfanta(new FantaPaginatorAdapter($paginatorAdapter));
    }

    /**
     * {@inheritdoc}
     */
    public function createPaginatorAdapter($query)
    {
        $query = Elastica_Query::create($query);
        return new TransformedPaginatorAdapter($this->searchable, $query, $this->transformer);
    }
}
