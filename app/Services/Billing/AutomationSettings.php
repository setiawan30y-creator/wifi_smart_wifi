<?php

namespace App\Services\Billing;

use App\Models\Setting;

/**
 * Baca/simpan pengaturan Otomatis Tagihan milik tenant aktif, dengan nilai bawaan.
 * Satu-satunya tempat yang tahu nama key setting otomatis tagihan.
 */
final class AutomationSettings
{
    public const DEFAULTS = [
        // Tagihan terbit
        'billing_auto_generate_enabled' => false,
        'billing_auto_generate_days_before_due' => 3,
        'billing_auto_generate_time' => '02:00',
        'billing_auto_generate_send_wa' => false,
        'billing_auto_generate_group_same_phone' => true,
        'billing_auto_generate_wa_template_ids' => [],
        // Jeda kirim WA (berlaku untuk semua pesan otomatis)
        'billing_auto_generate_wa_delay_min' => 5,
        'billing_auto_generate_wa_delay_max' => 20,
        // Pengingat
        'billing_reminder_time' => ReminderSchedule::DEFAULT_TIME,
        'billing_reminder_before_enabled' => false,
        'billing_reminder_before_days' => ReminderSchedule::DEFAULT_BEFORE_DAYS,
        'billing_reminder_before_wa_template_id' => null,
        'billing_overdue_wa_enabled' => false,
        'billing_reminder_after_days' => ReminderSchedule::DEFAULT_AFTER_DAYS,
        'billing_overdue_wa_template_id' => null,
        // Janji bayar
        'billing_payment_promise_wa_template_id' => null,
    ];

    /** Jenis pesan bawaan yang bisa diubah => key setting. */
    public const MESSAGE_KEYS = [
        'generated' => 'billing_message_generated',
        'before' => 'billing_message_before',
        'after' => 'billing_message_after',
        'arrears' => 'billing_message_arrears',
        'promise' => 'billing_message_promise',
    ];

    private const BOOLEANS = [
        'billing_auto_generate_enabled', 'billing_auto_generate_send_wa', 'billing_auto_generate_group_same_phone',
        'billing_reminder_before_enabled', 'billing_overdue_wa_enabled',
    ];

    private const LISTS = ['billing_auto_generate_wa_template_ids', 'billing_reminder_before_days', 'billing_reminder_after_days'];

    /**
     * Nilai siap pakai untuk tampilan (boolean, int, array).
     */
    public static function load(): array
    {
        $stored = Setting::query()->pluck('value', 'key')->all();
        $schedule = ReminderSchedule::fromSettings($stored);
        $values = [];

        foreach (self::DEFAULTS as $key => $default) {
            $raw = $stored[$key] ?? null;

            $values[$key] = match (true) {
                $raw === null || $raw === '' => $default,
                in_array($key, self::BOOLEANS, true) => in_array($raw, ['1', 'true', 'on', 'yes'], true),
                in_array($key, self::LISTS, true) => ReminderSchedule::parseDays($raw, 1_000_000),
                is_int($default) => (int) $raw,
                str_ends_with($key, '_template_id') => (int) $raw ?: null,
                default => substr((string) $raw, 0, 5),
            };
        }

        // Jadwal H+ lama (mulai H+X, maks. N kali) ditampilkan sebagai daftar hari.
        $values['billing_reminder_after_days'] = $schedule->afterDays ?: ReminderSchedule::DEFAULT_AFTER_DAYS;

        // Template tagihan terbit lama (satu template) tetap terbaca.
        if ($values['billing_auto_generate_wa_template_ids'] === [] && (int) ($stored['billing_auto_generate_wa_template_id'] ?? 0) > 0) {
            $values['billing_auto_generate_wa_template_ids'] = [(int) $stored['billing_auto_generate_wa_template_id']];
        }

        // Pesan bawaan: teks yang sedang berlaku (versi tenant atau teks asli).
        foreach (self::MESSAGE_KEYS as $type => $key) {
            $custom = trim((string) ($stored[$key] ?? ''));
            $values[$key] = $custom !== '' ? (string) $stored[$key] : BillingMessages::EDITABLE[$type];
        }

        return $values;
    }

    /**
     * Simpan data yang sudah divalidasi (lihat UpdateAutomationSettingsRequest).
     */
    public static function save(array $data): void
    {
        foreach (self::DEFAULTS as $key => $default) {
            $value = $data[$key] ?? null;

            if (in_array($key, self::BOOLEANS, true)) {
                $value = $value ? '1' : '0';
            } elseif (in_array($key, self::LISTS, true)) {
                $value = json_encode(collect($value ?? [])->map(fn ($v) => (int) $v)->unique()->values()->all());
            } else {
                $value = $value === null ? '' : (string) $value;
            }

            Setting::set($key, $value);
        }

        $templateIds = collect($data['billing_auto_generate_wa_template_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->values();
        Setting::set('billing_auto_generate_wa_template_id', (string) ($templateIds->first() ?? ''));

        // Pesan bawaan: disimpan kosong bila sama dengan teks asli, agar perbaikan teks asli
        // di versi aplikasi berikutnya tetap sampai ke tenant yang tidak mengubahnya.
        foreach (self::MESSAGE_KEYS as $type => $key) {
            $text = str_replace("\r\n", "\n", trim((string) ($data[$key] ?? '')));
            Setting::set($key, $text === '' || $text === BillingMessages::EDITABLE[$type] ? '' : $text);
        }

        // Key lama tetap diisi agar kode/laporan lama yang masih membacanya tetap konsisten.
        $after = collect($data['billing_reminder_after_days'] ?? [])->map(fn ($v) => (int) $v)->sort()->values();
        Setting::set('billing_overdue_days_after_due', (string) ($after->first() ?? 1));
        Setting::set('billing_overdue_max_reminders', (string) max(1, $after->count()));
    }
}
