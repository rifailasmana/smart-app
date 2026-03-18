<?php

namespace App\Http\Controllers;

use App\Models\Warung;
use App\Services\ReportExporter;

class ReportController extends Controller
{
    public function export(string $period, ReportExporter $exporter)
    {
        $user = auth()->user();

        if (!$user->warung_id) {
            abort(403);
        }

        $warung = Warung::findOrFail($user->warung_id);

        return $exporter->export($period, $warung);
    }
}

