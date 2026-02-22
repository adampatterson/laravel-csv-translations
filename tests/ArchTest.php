<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print', 'print_r'])
    ->each->not->toBeUsed();
