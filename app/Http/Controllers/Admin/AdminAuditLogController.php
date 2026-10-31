<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/AuditLogs', [
            'auditLogs' => AdminAuditLog::query()
                ->with('admin:id,name,email')
                ->latest('created_at')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
