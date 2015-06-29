<?php namespace App\Http\Controllers;

use Utils;
use Response;
use Auth;
use Input;
use App\Models\Subscription;

class IntegrationController extends Controller
{
    public function subscribe()
    {
        $eventId = Utils::lookupEventId(trim(Input::get('event')));

        if (!$eventId) {
            return Response::json('', 500);
        }

        $subscription = Subscription::where('account_id', '=', Auth::user()->account_id)->where('event_id', '=', $eventId)->first();

        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->account_id = Auth::user()->account_id;
            $subscription->event_id = $eventId;
        }

        $subscription->target_url = trim(Input::get('target_url'));
        $subscription->save();

        return Response::json('{"id":'.$subscription->id.'}', 201);
    }
}
