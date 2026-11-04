<?php

return [
    // Leave empty to use the Mozilla CA bundle shipped with this project.
    // Custom bundles must use an absolute path to a PEM file.
    'ca_bundle' => env('HTTP_CA_BUNDLE'),
];
