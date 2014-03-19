@echo off
vendor\bin\codecept run unit %1 --colors --skip-group database
