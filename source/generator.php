<?php

include 'config.php';

foreach ($pages as $key => $page) {
    echo file_put_contents('../' . $key . '.html', file_get_contents($url . $key . '.html'));
}