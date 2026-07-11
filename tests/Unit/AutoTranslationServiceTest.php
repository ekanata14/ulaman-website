<?php

use App\Services\AutoTranslationService;

beforeEach(function () {
    $this->service = new AutoTranslationService();
});

it('returns data unchanged when both locales are present', function () {
    $data = ['id' => 'Halo', 'en' => 'Hello'];
    $result = $this->service->fillMissingTranslations($data);
    expect($result)->toBe($data);
});

it('returns data unchanged when both locales are empty', function () {
    $data = ['id' => '', 'en' => ''];
    $result = $this->service->fillMissingTranslations($data);
    expect($result['id'])->toBe('');
    expect($result['en'])->toBe('');
});

it('fills English when only Indonesian is provided', function () {
    $mock = Mockery::mock('overload:Stichoza\GoogleTranslate\GoogleTranslate');
    $mock->shouldReceive('trans')->with('Halo', 'en', 'id')->andReturn('Hello');

    $result = $this->service->fillMissingTranslations(['id' => 'Halo', 'en' => '']);
    expect($result['en'])->toBe('Hello');
    expect($result['id'])->toBe('Halo');
});

it('fills Indonesian when only English is provided', function () {
    $mock = Mockery::mock('overload:Stichoza\GoogleTranslate\GoogleTranslate');
    $mock->shouldReceive('trans')->with('Hello', 'id', 'en')->andReturn('Halo');

    $result = $this->service->fillMissingTranslations(['id' => '', 'en' => 'Hello']);
    expect($result['id'])->toBe('Halo');
    expect($result['en'])->toBe('Hello');
});
