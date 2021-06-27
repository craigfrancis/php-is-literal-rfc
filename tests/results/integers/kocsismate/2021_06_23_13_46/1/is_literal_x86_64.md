### is_literal (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.4657|9.4299|0.0276|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1075|0.1015|0.0018|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2130|0.2130|0.0003|12 consecutive runs|
|micro_bench.php|time (sec)|1.3520|1.3520|0.0005|12 consecutive runs|
|concat.php|time (sec)|0.6210|0.6210|0.0007|12 consecutive runs|

##### Generated: 2021-06-23 11:57 based on commit [eaf31833ebfc710ab3cd3ab01e076eb8182e07a8](https://github.com/krakjoe/php-src/commit/eaf31833ebfc710ab3cd3ab01e076eb8182e07a8)
