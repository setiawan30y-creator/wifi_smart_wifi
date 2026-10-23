<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengaturan → Tampilan: warna tema tenant.
 */
class ThemeSettingController extends Controller
{
    public function update(Request $request)
    {
        $rules = ['theme_mode' => ['required', Rule::in(array_keys(Theme::MODES))]];
        foreach (array_keys(Theme::COLORS) as $key) {
            $rules[$key] = ['required', 'regex:/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'];
        }

        $data = $request->validate($rules, [
            'regex' => 'Kode warna :attribute tidak valid (contoh: #6366f1).',
        ], collect(Theme::COLORS)->map(fn ($meta) => strtolower($meta[2]))->all());

        foreach (array_keys(Theme::COLORS) as $key) {
            Setting::set($key, Theme::normalizeHex($data[$key]));
        }
        Setting::set('theme_mode', $data['theme_mode']);
        Theme::flush();

        return redirect()->route('settings.index', ['tab' => 'appearance'])->with('success', 'Warna tampilan disimpan dan langsung berlaku di semua halaman.');
    }

    public function reset()
    {
        Setting::query()->whereIn('key', [...array_keys(Theme::COLORS), 'theme_mode'])->delete();
        Theme::flush();

        return redirect()->route('settings.index', ['tab' => 'appearance'])->with('success', 'Warna tampilan dikembalikan ke bawaan.');
    }
}
