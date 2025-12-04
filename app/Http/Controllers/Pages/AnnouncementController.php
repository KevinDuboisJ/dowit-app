<?php

namespace App\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Events\BroadcastEvent;
use App\Models\Announcement;
use Exception;

class AnnouncementController extends Controller
{
  public function destroy(Announcement $announcement)
  {
    try {
      $announcement->delete();
      return redirect()->back();
    } catch (Exception $e) {
      logger($e);
    }
  }

  public function update(AnnouncementRequest $request, Announcement $announcement)
  {
    try {
      $announcement->update($request->prepareForDatabase());
      return redirect()->back();

    } catch (Exception $e) {
      logger($e);
    }
  }

  public function store(AnnouncementRequest $request)
  {
    $announcement = Announcement::create($request->prepareForDatabase());
    broadcast(new BroadcastEvent($announcement, 'announcement_created', 'dashboard-announce'));
    return redirect()->back();
  }

  public function markAsRead(Announcement $announcement, Authenticatable $user)
  {
    // Add the user ID to the `read_by` column if it's not already there
    $readBy = $announcement->read_by ?? [];
    if (!in_array($user->id, $readBy)) {
      $readBy[] = $user->id;
      $announcement->read_by = $readBy;
      $announcement->save();
    }

    return to_route('dashboard.index'); // return redirect()->back();
  }
}
