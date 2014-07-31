<?php

namespace Jacobemerick\KMeans;

use Exception;

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

        do {
            if (empty($centroids)) {
                $centroids = $this->getInitialCentroids($cluster_count, $method);
            } else {
                $centroids = $this->calculateCentroids($this->clustered_data);
            }

            $new_clustered_data = array_fill(0, $cluster_count, []);
            foreach ($this->data as $observation) {
                $closest_centroid = $this->calculateClosestCentroid($observation, $centroids);
                array_push($new_clustered_data[$closest_centroid], $observation);
            }
        } while ($this->assignmentConvergenceCheck((array) $this->clustered_data, $new_clustered_data) === false);

        $this->centroids = $centroids;
        // todo calculate centroid distances

        return $this->getClusteredData();
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
        $data_range = $this->calculateRange($this->data);
        $random_points = [];

        for ($i = 0; $i < $cluster_count; $i++) {
            $random_points[$i] = array_fill(0, count($this->data), null);
            foreach ($data_range as $key => $range) {
                $random_points[$i][$key] = ($range['min'] + lcg_value() * ($range['max'] - $range['min']));
            }
        }

        return $random_points;
    }

    /**
     * calculate centroids based on clustered data
     *
     * @param   $clustered_data  array  multi-dimensional array of clustered data
     * @return                   array  list of centroids
     */
    protected function calculateCentroids(array $clustered_data)
    {
        $centroids = [];
        foreach ($clustered_data as $cluster) {
            $range = $this->calculateRange($cluster);
            $centroid = [];
            foreach ($range as $dimension) {
                array_push($centroid, ($dimension['max'] - $dimension['min']) / 2);
            }
            array_push($centroids, $centroid);
        }
        return $centroids;
    }

    /**
     * calculate the closest centroid to an observation
     *
     * @param   $observation  array    observation from data set
     * @param   $centroids    array    list of centroids
     * @return                integer  index that observation should be clustered into
     */
    protected function calculateClosestCentroid(array $observation, array $centroids)
    {
        $centroid_distance = [];
        foreach ($centroids as $centroid) {
            array_push($centroid_distance, $this->calculateDistance($observation, $centroid));
        }
        asort($centroid_distance);
        $centroid_distance = array_flip($centroid_distance);
        return array_shift($centroid_distance);
    }

    /**
     * check to see if clustered data has converged yet
     * if not, reassing new data to internal holder and return false to re-run script
     *
     * @param   $clustered_data      array    the old holder of clustered_data
     * @param   $new_clustered_data  array    new clustered_data to check against
     * @return                       boolean  whether or not convergence has occurred
     */
    protected function assignmentConvergenceCheck(array $clustered_data, array $new_clustered_data)
    {
        if (empty($clustered_data)) {
            $this->clustered_data = $new_clustered_data;
            return false;
        }

        foreach ($clustered_data as $key => $cluster) {
            foreach ($cluster as $observation) {
                if (!in_array($observation, $new_clustered_data[$key])) {
                    $this->clustered_data = $new_clustered_data;
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * helper method to get the range of a set of data
     *
     * @param   $data  array  list of points to determine range of
     * @return         array  formatted return of range based on the data
     */
    protected function calculateRange($data)
    {
        $data_range = array_fill(0, count(current($data)), ['min' => null, 'max' => null]);
        foreach ($data as $observation) {
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

        return $data_range;
    }

    /**
     * helper method to determine the euclidean distance between two n-dimensional points
     * well, sum of squares, as the actual distance is unneeded - just the relative distance
     *
     * @param   $point_a  array  list of numeric values that determine a point
     * @param   $point_b  array  list of numeric values that determine a point
     * @return            float  distance between the points
     */
    protected function calculateDistance($point_a, $point_b)
    {
        $distance = 0;
        for ($i = 0, $count = count($point_a); $i < $count; $i++) {
            $difference = $point_a[$i] - $point_b[$i];
            $distance += pow($difference, 2);
        }
        return $distance;
    }

}

