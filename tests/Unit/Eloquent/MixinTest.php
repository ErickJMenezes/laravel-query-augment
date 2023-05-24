<?php

use Illuminate\Support\Facades\DB;

it('works!', function () {
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
            } elseif ($testing->text == 'bar' || $testing->text == 'baz') {
                return 'error!';
            } else {
                return 'another error!';
            }
        }, 'value')
        ->first();

    expect($item)
        ->toBeObject()
        ->and($item)
        ->toHaveProperty('value', 'yay!');
});
