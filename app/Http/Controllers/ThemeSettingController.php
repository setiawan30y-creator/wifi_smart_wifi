<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengaturan → Tampilan & Warna.
 */
class ThemeSettingController extends Controller
{
    private const HEX = 'regex:/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    public function update(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $theme = Theme::resolve($data);

        foreach ($theme['colors'] as $key => $value) {
            Setting::set($key, $value);
        }
        foreach ($theme['options'] as $key => $value) {
            Setting::set($key, $value);
        }
        foreach ($theme['surfaces'] as $mode => $surfaces) {
            foreach ($surfaces as $surface => $value) {
                Setting::set(Theme::surfaceKey($mode, $surface), $value ?? '');
            }
        }
        Theme::flush();

        return redirect()->route('settings.index', ['tab' => 'appearance'])->with('success', 'Tampilan disimpan dan langsung berlaku di semua halaman.');
    }

    /**
     * CSS pratinjau dari isian form (tanpa menyimpan).
     */
    public function preview(Request $request)
    {
        $theme = Theme::resolve($request->only(Theme::keys()));

        return response()->json([
            'css' => Theme::css($theme),
            'tones' => Theme::tones($theme),
            'font_url' => Theme::fontUrl($theme),
        ]);
    }

    public function reset()
    {
        Setting::query()->whereIn('key', Theme::keys())->delete();
        Theme::flush();

        return redirect()->route('settings.index', ['tab' => 'appearance'])->with('success', 'Tampilan dikembalikan ke bawaan.');
    }

    private function rules(): array
    {
        $rules = [
            'theme_mode' => ['required', Rule::in(array_keys(Theme::MODES))],
            'theme_font' => ['required', Rule::in(array_keys(Theme::FONTS))],
            'theme_font_size' => ['required', Rule::in(array_keys(Theme::FONT_SIZES))],
            'theme_radius' => ['required', Rule::in(array_keys(Theme::RADII))],
            'theme_shadow' => ['required', Rule::in(array_keys(Theme::SHADOWS))],
            'theme_table_striped' => ['nullable', 'boolean'],
        ];

        foreach (array_keys(Theme::COLORS) as $key) {
            $rules[$key] = ['required', self::HEX];
        }

        foreach (['dark', 'light'] as $mode) {
            foreach (array_keys(Theme::SURFACES) as $surface) {
                $rules[Theme::surfaceKey($mode, $surface)] = ['nullable', self::HEX];
            }
        }

        return $rules;
    }

    private function messages(): array
    {
        return ['regex' => 'Kode warna :attribute tidak valid (contoh: #6366f1).'];
    }

    private function attributes(): array
    {
        $attributes = collect(Theme::COLORS)->map(fn ($meta) => strtolower($meta[2]))->all();

        foreach (['dark' => 'mode gelap', 'light' => 'mode terang'] as $mode => $label) {
            foreach (Theme::SURFACES as $surface => $meta) {
                $attributes[Theme::surfaceKey($mode, $surface)] = strtolower($meta[0]) . ' (' . $label . ')';
            }
        }

        return $attributes;
    }
}
