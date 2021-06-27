### is_literal (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.7235|9.7818|0.0271|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1016|0.1017|0.0009|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2270|0.2260|0.0010|12 consecutive runs|
|micro_bench.php|time (sec)|1.4240|1.4250|0.0009|12 consecutive runs|
|concat.php|time (sec)|0.6340|0.6330|0.0010|12 consecutive runs|

##### Generated: 2021-06-23 10:02 based on commit [eaf31833ebfc710ab3cd3ab01e076eb8182e07a8](https://github.com/krakjoe/php-src/commit/eaf31833ebfc710ab3cd3ab01e076eb8182e07a8)
