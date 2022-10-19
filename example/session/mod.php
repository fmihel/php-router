<?php
use fmihel\router;

error_log(print_r(router::$data,true));
router::out('mod.php response ok');