## WinkBrace Oracle library

### Installation

download this repository or install via composer. Put this line in your composer.json file.

    require {
        "winkbrace/oracle": "master-dev"
    }

After running `composer install -o` edit the config.php file located in the root of this library with your database connection information.

### Logging

You can enable the option to log each query in config.php. The QueryLogger implementation is currently defined in the bootstrap file.

### Testing

If you want to run the test suite, first execute the sql script located at

    tests/prepare_test_schema.sql

Make sure you create a _log folder inside the tests folder if that is missing. I keep the logs outside version control. The coverage report will be created in this folder at `tests/_log/coverage/index.html`

If you are running these tests on windows, make sure you have [ansicon](https://github.com/adoxa/ansicon/downloads "ansicon") installed to see colored output. 

To run all unit tests, there are two convenient helper batch files (on Windows only)

- unit.bat: run all tests and create html coverage report. (slow (4 secs))
- t.bat: run all tests except those in the @group database and without the coverage report (fast)
	
To run only 1 test set type for example:

	t StatementTest.php
	
