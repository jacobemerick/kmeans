kmeans via PHP
==============

This handly little class will calculate the k-means for a set of observations using PHP. k-means is a cool way to cluster data into groups based on relation - like clustering geographical data (using lat/lng) into a digestible summary. It is useful for detecting patterns in large data sets.

## Usage

Let's say that you wanted to cluster a data set. The data must be in a multi-dimensional array, each value a numeric, though the size of each row has no constraint (n-dimensions ftw).

```php
$array = [
    [1, 1, 3],
    [3, 7, 6],
    [5, 8, 3],
    [1, 2, 1],
    [9, 10, 8],
    [4, 4, 4],
];
```

By observation you may suspect that this data can be clustered into 3 separate sets. To test, run the class.

```php
$kmeans = new Jacobemerick\KMeans\Kmeans($array);
$kmeans->cluster(3); // cluster into three sets

$clustered_data = $kmeans->getClusteredData();
// $clustered_data = [
//     [[1, 1, 3], [1, 2, 1]],
//     [[3, 5, 6], [5, 4, 3], [4, 4, 4]],
//     [[9, 10, 8]],
// ];

$centroids = $kmeans->getCentroids();
// $centroids = [
//     [1, 1.5, 2],
//     [4, 4.33, 4.33],
//     [9, 10, 8],
// ];
```

Note: larger data sets will be more consistent - if you run this example multiple times your results may vary.

## Installation

Through [composer](http://getcomposer.org):

```bash
$ composer require jacobemerick/kmeans:~1.0
```

