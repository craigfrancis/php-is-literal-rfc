
Clone `github.com/php/php-src` into the './src/v0/' folder.

Make a copy in './src/v1/', and apply the patch.

Compile them both.

The basic set of tests can be run with:

	./src/v1/sapi/cli/php ./src/v1/run-tests.php --show-diff ./tests/000.phpt

The performance tests can be run with:

	for F in v0 v1 v0 v1 v0 v1 v0 v1; do echo -n $F; TEST=1 ./src/$F/sapi/cli/php ./tests/001.phpt; done
