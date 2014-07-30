<?php

namespace Jacobemerick\KMeans;

use Exception;

class KMeans
{

    // initial, unmodified data field
    protected $data;

    // convenience parameter to track observation size
    protected $observation_size;

    // array of modified data, multi-dimensional based on cluster_count
    protected $clustered_data;

    // array of centroids based on cluster_count
    protected $centroids;

    // array of centroid distance, useful for testing different cluster_counts
    protected $centroid_distance;

    // acceptable methods for clustering
    protected static $ACCEPTED_CLUSTERING_METHODS = [
        'random',
        'forgy',
    ];

    /**
     * basic construct that accepts the initial list of observations
     * exception thrown if data is not large enough for clustering
     *
     * @param  $data  array  list of observations, each observation a same-length list of numeric values
     */
    public function __construct(array $data)
    {
        if (count($data) < 2) {
            throw new Exception('Data must have more than one row');
        }

        $this->data = $data;
        $this->observation_size = count(current($data));
    }

    /**
     * primary worker for the clustering logic
     * broken out from construct due to processing concerns
     * hydrates the important parameters (clustered data, centroids, etc)
     *
     * @param   $cluster_count  integer  how many clusters to break the data into
     * @param   $method         string   the preferred method for clustering ('random' or 'forgy')
     * @return                  array    clustered data from process (getClusteredData)
     */
    public function cluster($cluster_count, $method = 'forgy')
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

        $initial_centroids = $this->getInitialCentroids($cluster_count, $method);
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
     * contained switch for initialization method
     *
     * @param   $cluster_count  integer  how manu clusters are requested
     * @param   $method         string   type of initialization requested
     * @return                  array    list of centroids for initialization
     */
    protected function getInitialCentroids($cluster_count, $method)
    {
        if ($method == 'forgy') {
            return $this->getForgyInitialization($cluster_count);
        }
        if ($method == 'random') {
            return $this->getRandomInitialization($cluster_count);
        }
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
        $random_keys = array_rand($this->data, $cluster_count);
        $random_keys = array_flip($random_keys);
        return array_intersect_key($this->data, $random_keys);
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
        $data_range = array_fill(0, $this->observation_size, ['min' => null, 'max' => null]);
        foreach ($this->data as $observation) {
            $key = 0;
            foreach ($observation as $value) {
                if ($data_range[$key]['min'] === null || $data_range[$key]['min'] > $value) {
                    $data_range[$key]['min'] = $value;
                }
                if ($data_range[$key]['max'] === null || $data_range[$key]['max'] < $value) {
                    $data_range[$key]['max'] = $value;
                }
                $key++;
            }
        }

        $random_points = [];
        for ($i = 0; $i < $cluster_count; $i++) {
            $random_points[$i] = array_fill(0, $this->observation_size, null);
            foreach ($data_range as $key => $range) {
                $random_points[$i][$key] = ($range['min'] + lcg_value() * ($range['max'] - $range['min']));
            }
        }

        return $random_points;
    }

}

