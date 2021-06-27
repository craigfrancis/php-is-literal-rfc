### is_literal (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.3452|9.3948|0.0340|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1005|0.1013|0.0009|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2110|0.2110|0.0005|12 consecutive runs|
|micro_bench.php|time (sec)|1.3510|1.3500|0.0006|12 consecutive runs|
|concat.php|time (sec)|0.6190|0.6150|0.0023|12 consecutive runs|

##### Generated: 2021-06-23 11:35 based on commit [eaf31833ebfc710ab3cd3ab01e076eb8182e07a8](https://github.com/krakjoe/php-src/commit/eaf31833ebfc710ab3cd3ab01e076eb8182e07a8)
