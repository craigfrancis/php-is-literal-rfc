### master2 (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.3766|9.3803|0.0336|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1006|0.1009|0.0005|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2140|0.2140|0.0012|12 consecutive runs|
|micro_bench.php|time (sec)|1.3370|1.3390|0.0015|12 consecutive runs|
|concat.php|time (sec)|0.6010|0.6010|0.0005|12 consecutive runs|

##### Generated: 2021-06-23 16:21 based on commit [fb701948502e3b301ed8030b985ac614db963c28](https://github.com/php/php-src/commit/fb701948502e3b301ed8030b985ac614db963c28)
