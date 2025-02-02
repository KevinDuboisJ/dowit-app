<?php
// This is not being used
namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Inertia\Inertia;
use Carbon\Carbon;
use Redirect;

class NotificationController extends Controller
{

  public function index(Request $request)
  {

    return Inertia::render('Notification', array_merge($data, [
      'pageProps' => [
        'notifications' => Notification::with('user')->orderBy('read_at', 'ASC')->get(),
      ]
    ]));
  }

  public function update(Request $request, Notification $notification)
  {
    $notification->update(['read_at' => Carbon::now()->format('Y-m-d H:i:s')]);
    return Redirect::route('notifications.index');
  }
}
