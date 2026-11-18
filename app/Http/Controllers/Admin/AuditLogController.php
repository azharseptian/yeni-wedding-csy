<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.audit', [
            'logs' => AuditLog::with(['user', 'auditable'])->when($request->event, fn ($q, $e) => $q->where('event', $e))->latest('id')->paginate(40)->withQueryString(),
            'events' => AuditLog::distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}
