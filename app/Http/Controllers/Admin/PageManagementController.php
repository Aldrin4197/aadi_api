<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PageAvailability;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageManagementController extends Controller
{
    public function index(PageAvailability $pages)
    {
        return Inertia::render('Admin/Pages/Index', ['pages' => $pages->all()]);
    }

    public function update(Request $request, PageAvailability $pages, string $page)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $pages->update($page, $data['enabled']);

        return back()->with('success', $data['enabled'] ? 'Page enabled.' : 'Page disabled.');
    }
}
