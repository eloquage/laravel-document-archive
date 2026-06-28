<?php

use Eloquage\LaravelDocumentArchive\Services\SpecialSourceService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('legacy');
});

it('returns error for malformed facebook html without messages', function () {
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/Facebook_Conversa_Mimi_Morticia/PROVA_C15_Mensagens_Facebook_Completas.html',
        '<html><body>not a message export</body></html>',
    );

    $result = app(SpecialSourceService::class)->readFacebook('479-26-0T9MTS', query: 'missing');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('No matching Facebook messages');
});

it('returns error when whatsapp directory has no matching files', function () {
    $result = app(SpecialSourceService::class)->readWhatsApp('479-26-0T9MTS', query: 'hello');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('No matching WhatsApp');
});

it('sanitizes utf8 content from whatsapp files', function () {
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/WHATSAPP-CHAT-test.txt',
        "Olá mundo\x00",
    );

    $result = app(SpecialSourceService::class)->readWhatsApp('479-26-0T9MTS');

    expect($result['success'])->toBeTrue()
        ->and($result['messages'][0])->toContain('Olá mundo')
        ->and($result['messages'][0])->not->toContain("\0");
});
