<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ExpireATMethodTest extends TestCase
{

    /**
     *  test to see if time is expired or not.
     *
     * @return void
     * @test
     */
    public function expiry_time_check()
    {
        $due_time = Carbon::addDay(1);
        $created_at = Carbon::now();
        
        $difference = $due_time->diffInHours($created_at);


        if($difference <= 90)
            $time = $due_time;
        elseif ($difference <= 24) {
            $time = $created_at->addMinutes(90);
        } elseif ($difference > 24 && $difference <= 72) {
            $time = $created_at->addHours(16);
        } else {
            $time = $due_time->subHours(48);
        }

        $response = $time->format('Y-m-d H:i:s');
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);
    }
}
