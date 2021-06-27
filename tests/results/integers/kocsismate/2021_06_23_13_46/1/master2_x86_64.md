### master2 (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.7415|9.4483|0.1448|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1010|0.1005|0.0005|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2150|0.2150|nan|12 consecutive runs|
|micro_bench.php|time (sec)|1.3340|1.3350|0.0009|12 consecutive runs|
|concat.php|time (sec)|0.6010|0.6010|0.0008|12 consecutive runs|

##### Generated: 2021-06-23 12:00 based on commit [fb701948502e3b301ed8030b985ac614db963c28](https://github.com/php/php-src/commit/fb701948502e3b301ed8030b985ac614db963c28)
