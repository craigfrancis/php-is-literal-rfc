### master2 (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.4661|9.4568|0.0145|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1006|0.1010|0.0004|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2120|0.2130|0.0012|12 consecutive runs|
|micro_bench.php|time (sec)|1.3350|1.3370|0.0016|12 consecutive runs|
|concat.php|time (sec)|0.6010|0.6010|0.0021|12 consecutive runs|

##### Generated: 2021-06-23 11:39 based on commit [fb701948502e3b301ed8030b985ac614db963c28](https://github.com/php/php-src/commit/fb701948502e3b301ed8030b985ac614db963c28)
