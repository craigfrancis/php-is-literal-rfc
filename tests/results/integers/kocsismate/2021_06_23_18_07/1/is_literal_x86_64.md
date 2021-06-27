### is_literal (opcache: 1, preloading: 0, JIT: 0)

|  Benchmark   |    Metric    |   Average   |   Median    |    StdDev   | Description |
|--------------|--------------|-------------|-------------|-------------|-------------|
|Laravel demo app|time (sec)|9.3951|9.3737|0.0276|12 consecutive runs, 3000 requests|
|Symfony demo app|time (sec)|0.1014|0.1010|0.0005|12 consecutive runs, 3000 requests|
|bench.php|time (sec)|0.2140|0.2130|0.0005|12 consecutive runs|
|micro_bench.php|time (sec)|1.3860|1.3850|0.0005|12 consecutive runs|
|concat.php|time (sec)|0.6210|0.6220|0.0014|12 consecutive runs|

##### Generated: 2021-06-23 16:17 based on commit [eaf31833ebfc710ab3cd3ab01e076eb8182e07a8](https://github.com/krakjoe/php-src/commit/eaf31833ebfc710ab3cd3ab01e076eb8182e07a8)
