<?php

use Illuminate\Support\Facades\DB;

it('compiles a single condition', function () {
    DB::table('testing')
        ->insert([
            'text' => 'foo',
            'number' => 50,
        ]);

    $item = DB::table('testing')
        ->where('number', 50)
        ->addSelectCase(function ($testing) {
            if ($testing->text == 'foo') {
                return 'yay!';
            }
        }, 'value')
        ->first();

    expect($item)
        ->toBeObject()
        ->and($item)
        ->toHaveProperty('value', 'yay!');
});

it('compiles else statement', function () {
    DB::table('testing')
        ->insert([
            'text' => 'foo',
            'number' => 50,
        ]);

    $item = DB::table('testing')
        ->where('number', 50)
        ->addSelectCase(function ($testing) {
            if ($testing->text == 'bar') {
                return 'error';
            } else {
                return 'yay!';
            }
        }, 'value')
        ->first();

    expect($item)
        ->toBeObject()
        ->and($item)
        ->toHaveProperty('value', 'yay!');
});

it('compiles elseif statements', function () {
    DB::table('testing')
        ->insert([
            'text' => 'foo',
            'number' => 50,
        ]);

    $item = DB::table('testing')
        ->where('number', 50)
        ->addSelectCase(function ($testing) {
            if ($testing->text == 'bar') {
                return 'error';
            } elseif ($testing->text == 'foo') {
                return 'yay!';
            } else {
                return 'error 2';
            }
        }, 'value')
        ->first();

    expect($item)
        ->toBeObject()
        ->and($item)
        ->toHaveProperty('value', 'yay!');
});

it('compiles sub queries', function () {
    DB::table('testing')
        ->insert([
            'text' => 'foo',
            'number' => 50,
        ]);
    DB::table('testing')
        ->insert([
            'text' => 'yay!',
            'number' => 100,
        ]);

    $item = DB::table('testing')
        ->where('number', 50)
        ->addSelectCase(function ($testing) {
            if ($testing->text == 'foo') {
                return DB::table('testing')
                    ->where('number', 100)
                    ->select('text');
            }
        }, 'value')
        ->first();

    expect($item)
        ->toBeObject()
        ->and($item)
        ->toHaveProperty('value', 'yay!');
});
