<?php

use Oracle\Connection;
use Oracle\Support\Config;

// setup QueryLogger implementation
Config::put('logger', new \Oracle\Log\WebQueryLog(new Connection()));
