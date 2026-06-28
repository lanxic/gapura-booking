<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;

class LegalController extends Controller
{
    public function show(string $page)
    {
        $allowed = ['privacy-policy', 'terms-of-service'];

        if (!in_array($page, $allowed)) {
            abort(404);
        }

        $key     = str_replace('-', '_', $page);
        $content = SystemSetting::get('legal', $key, '');
        $title   = $page === 'privacy-policy' ? 'Kebijakan Privasi' : 'Syarat & Ketentuan';

        return view('legal.show', compact('content', 'title'));
    }
}
