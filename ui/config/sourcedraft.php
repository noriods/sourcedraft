<?php

return [
    'root' => env('SOURCEDRAFT_ROOT', base_path('..')),
    'python' => env('SOURCEDRAFT_PYTHON', base_path('../.venv/bin/python')),
    'soffice' => env('SOFFICE', 'soffice'),
];
