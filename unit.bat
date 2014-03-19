@echo off
vendor\bin\codecept run unit %1 --coverage --colors --html
