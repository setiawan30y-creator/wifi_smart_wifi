<?php

namespace App\Http\Controllers;

use App\Models\WaTemplate;
use Illuminate\Http\Request;

class WaTemplateController extends Controller
{
    /**
     * Display a listing of the WA templates.
     */
    public function index()
    {
        return redirect()->route('settings.index', ['tab' => 'wa-templates']);
    }

    /**
     * Store a newly created WA template in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wa_templates,name|alpha_dash',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $validated['is_system'] = false;

        WaTemplate::create($validated);

        return redirect()->route('settings.index', ['tab' => 'wa-templates'])->with('success', 'Template WA baru berhasil ditambahkan.');
    }

    /**
     * Update the specified WA template in storage.
     */
    public function update(Request $request, WaTemplate $waTemplate)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];

        // Only allow name updates if it's not a system template
        if (!$waTemplate->is_system) {
            $rules['name'] = 'required|string|max:255|alpha_dash|unique:wa_templates,name,' . $waTemplate->id;
        }

        $validated = $request->validate($rules);

        // Force is_system protection
        if ($waTemplate->is_system) {
            unset($validated['name']);
        }

        $waTemplate->update($validated);

        return redirect()->route('settings.index', ['tab' => 'wa-templates'])->with('success', 'Template WA berhasil diperbarui.');
    }

    /**
     * Remove the specified WA template from storage.
     */
    public function destroy(WaTemplate $waTemplate)
    {
        // Block deleting system templates
        if ($waTemplate->is_system) {
            return redirect()->route('settings.index', ['tab' => 'wa-templates'])->with('error', 'Template bawaan sistem tidak diperbolehkan untuk dihapus.');
        }

        $waTemplate->delete();

        return redirect()->route('settings.index', ['tab' => 'wa-templates'])->with('success', 'Template WA berhasil dihapus.');
    }
}
