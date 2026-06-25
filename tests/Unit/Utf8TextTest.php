<?php

use Eloquage\LaravelDocumentArchive\Support\Utf8Text;

it('strips null bytes from text', function () {
    expect(Utf8Text::sanitize("hello\0world"))->toBe('helloworld');
});

it('returns valid utf-8 text unchanged', function () {
    expect(Utf8Text::sanitize('Texto português com acentuação.'))->toBe('Texto português com acentuação.');
});

it('removes invalid control characters', function () {
    $sanitized = Utf8Text::sanitize("line\x01break");

    expect($sanitized)->toBe('linebreak');
});
