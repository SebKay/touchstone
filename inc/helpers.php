<?php

namespace SebKay\Touchstone;

const NAME = 'Touchstone';
const VERSION = '2';
const SQLITE_FILE_PATH = 'db/sqlite.db';

const CMD_INTRO = [
    '<options=bold>' . NAME . ' v' . VERSION,
    '',
];

const CMD_ICONS = [
    'check' => '<fg=green>✓</>',
    'cross' => '<fg=red>𐄂</>',
    'loading' => '<fg=magenta>==></>',
];
