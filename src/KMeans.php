<?php

namespace Jemerick\KMeans;

class KMeans
{

    // initial, unmodified data field
    protected $data;

    // array of modified data, multi-dimensional based on cluster_count
    protected $clustered_data;

    // array of centroids based on cluster_count
    protected $centroids;

    // array of centroid distance, useful for testing different cluster_counts
    protected $centroid_distance;

    // acceptable methods for clustering
    protected static $ACCEPTED_CLUSTERING_METHODS = [
        'Random',
        'Forgy',
    ];

    /**
     * basic construct that accepts the initial list of data points
     * exception thrown if data is not suitable for clustering
     *
     * @param  $data  array  list of observations, each observation having n-dimensions, each row with identical length
     */
    public function __construct(array $data)
    {
        if (count($data) < 2) {
            throw new Exception('Data must have more than one row');
        }

        $this->data = $data;
    }

    /**
     * primary worker for the clustering logic
     * broken out from construct due to processing concerns
     * hydrates the important parameters (clustered data, centroids, etc)
     *
     * @param   $cluster_count  integer  how many clusters to break the data into
     * @param   $method         string   the preferred method for clustering ('Random' or 'Forgy')
     * @return                  array    clustered data from process (getClusteredData)
     */
    public function cluster($cluster_count, $method = 'Random')
    {
        if ($cluster_count < 2) {
            throw new Exception('Cluster count must be greater than 1');
        }
        if ($cluster_count > count($this->data)) {
            throw new Exception('Cluster count must be greater than the number of data points');
        }

        if (!in_array($method, self::$ACCEPTED_CLUSTERING_METHODS)) {
            throw new Exception("Unrecognized method passed into cluster: {$method}");
        }
    }

    /**
     * simple getter to fetch the centroids
     * will throw an exception if centroids have not been set yet
     *
     * @return  array  list of centroids
     */
    public function getCentroids()
    {
        if (empty($this->centroids)) {
            throw new Exception('Centroids have not been hydrated yet - run cluster method first');
        }

        return $this->centroids;
    }

    /**
     * simple getter to fetch the clustered data
     * will throw an exception if clustered data have not been set yet
     *
     * @return  array  multi-dimensional array of clustered data
     */
    public function getClusteredData()
    {
        if (empty($this->clustered_data)) {
            throw new Exception('Clustered data have not been hydrated yet - run cluster method first');
        }

        return $this->clustered_data;
    }

    /**
     * simple getter to fetch centroid distance
     * this number is helpful for determining cluster count for repeat runs
     * will throw an exception if cluster has not been run yet
     *
     * @return  array  list of centroid distances
     */
    public function getCentroidDistance()
    {
        if (empty($this->centroid_distance)) {
            throw new Exception('Centroid distance has not been hydrated yet - run cluster method first');
        }

        return $this->centroid_distance;
    }

    /**
     * get initialization points from random selection
     * try to lean towards center of data set
     *
     * @param   $cluster_count  integer  number of points to fetch
     * @return                  array    list of initialization points
     */
    protected function getRandomInitialization($cluster_count)
    {
    }

    /**
     * get initialization points from random points in data set
     * tends to spread out points more
     *
     * @param   $cluster_count  integer  number of points to fetch
     * @return                  array    list of initialization points
     */
    protected function getForgyInitialization($cluster_count)
    {
    }

}

