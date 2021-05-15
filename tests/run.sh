#--------------------------------------------------
# Check test projects

export REDIRECT_STATUS=200;
export REQUEST_METHOD="GET";
export REQUEST_SCHEME="https";
export REQUEST_URI="/";
export APP_ENV="local";
export APP_DEBUG=true;
export SESSION_DRIVER=cookie;
export LOG_LEVEL=warning;
export CONTENT_TYPE="text/html; charset=utf-8";
export SCRIPT_PATH="index.php";
export SCRIPT_NAME="/public/${SCRIPT_PATH}";

export SCRIPT_FILENAME="./500-laravel${SCRIPT_NAME}";

../src/b/sapi/cgi/php-cgi > output-laravel.html;

export SCRIPT_FILENAME="./600-symfony${SCRIPT_NAME}";

../src/b/sapi/cgi/php-cgi > output-symfony.html;

#--------------------------------------------------
# Run

rm -f output.txt;

echo "Test 1" | tee -a output.txt;

for V in $(printf 'a b c %.0s' {1..11}); do echo -n $V >> output.txt; ../src/$V/sapi/cli/php ./001.phpt >> output.txt; done

echo "Test 2" | tee -a output.txt;

export SCRIPT_FILENAME="./500-laravel${SCRIPT_NAME}";
export APP_ENV="production"
export APP_DEBUG=false

for V in $(printf 'a b c %.0s' {1..11}); do echo -n $V >> output.txt; ../src/$V/sapi/cgi/php-cgi "-T10" > /dev/null 2>> output.txt; done

echo "Test 3" | tee -a output.txt;

export SCRIPT_FILENAME="./600-symfony${SCRIPT_NAME}";
export APP_ENV="prod"

for V in $(printf 'a b c %.0s' {1..11}); do echo -n $V >> output.txt; ../src/$V/sapi/cgi/php-cgi "-T10" > /dev/null 2>> output.txt; done

perl -0777 -pe 's/(^|\n)a( |\nElapsed time: )([0-9\.]+)( sec)?\nb( |\nElapsed time: )([0-9\.]+)( sec)?\nc( |\nElapsed time: )([0-9\.]+)( sec)?/\n$3\t$6\t$9/g' -i output.txt;

echo "Done";
