<?php

namespace Database\Seeders;

use App\Models\Config;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Config::updateOrCreate([
            'key' => 'Email Password',
        ], [
            'key' => 'Email Password',
            'value' => 'ParaGames2018',
            'type' => 'password',
        ]);

        Config::updateOrCreate([
            'key' => 'USD to IDR',
        ], [
            'key' => 'USD to IDR',
            'value' => '14276.50',
            'type' => 'float',
        ]);

        Config::updateOrCreate([
            'key' => 'Start Exchange Date',
        ], [
            'key' => 'Start Exchange Date',
            'value' => '2022-03-01',
            'type' => 'date',
        ]);

        Config::updateOrCreate([
            'key' => 'End Exchange Date',
        ], [
            'key' => 'End Exchange Date',
            'value' => '2022-04-01',
            'type' => 'date',
        ]);
    }
}
