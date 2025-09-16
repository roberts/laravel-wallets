<?php

describe('Architecture Tests', function () {
    arch('prevents debugging functions')
        ->expect(['dd', 'dump', 'ray'])
        ->each->not->toBeUsed();
});
