### master2 (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.8281|9.7696|0.0456|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1021|0.1014|0.0006|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2250|0.2250|0.0000|12 consecutive runs|
|micro_bench.php|time (sec)|1.4150|1.4190|0.0018|12 consecutive runs|
|concat.php|time (sec)|0.6130|0.6140|0.0007|12 consecutive runs|

##### Generated: 2021-06-23 10:06 based on commit [fb701948502e3b301ed8030b985ac614db963c28](https://github.com/php/php-src/commit/fb701948502e3b301ed8030b985ac614db963c28)
