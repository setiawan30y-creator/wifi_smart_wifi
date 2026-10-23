<?php

namespace App\Services\Billing;

/**
 * Jadwal pengingat WA tagihan per tenant, dibaca dari tabel settings.
 *
 *  - Sebelum jatuh tempo: daftar H- (mis. [3, 1, 0]; 0 = pada hari jatuh tempo).
 *  - Sesudah jatuh tempo: daftar H+ (mis. [1, 3, 7]) untuk tagihan yang belum dibayar.
 *  - Jam kirim: pengingat tidak dikirim sebelum jam ini.
 *
 * Aturan "ketinggalan" (mis. server mati beberapa hari): yang dikirim hanya tahap terbaru
 * yang sudah tercapai, bukan semua tahap yang terlewat sekaligus.
 */
final class ReminderSchedule
{
    public const BEFORE_CHOICES = [7, 5, 3, 2, 1, 0];
    public const AFTER_CHOICES = [1, 2, 3, 5, 7, 10, 14, 21, 30];

    public const DEFAULT_BEFORE_DAYS = [3, 1, 0];
    public const DEFAULT_AFTER_DAYS = [1, 3, 7];
    public const DEFAULT_TIME = '08:00';

    private const MAX_BEFORE = 31;
    private const MAX_AFTER = 120;

    /**
     * @param  int[]  $beforeDays  urut besar ke kecil (H-7, H-3, H-1, H)
     * @param  int[]  $afterDays   urut kecil ke besar (H+1, H+3, H+7)
     */
    public function __construct(
        public readonly bool $beforeEnabled,
        public readonly array $beforeDays,
        public readonly ?int $beforeTemplateId,
        public readonly bool $afterEnabled,
        public readonly array $afterDays,
        public readonly ?int $afterTemplateId,
        public readonly string $time,
    ) {
    }

    public static function fromSettings(array $settings): self
    {
        $before = array_key_exists('billing_reminder_before_days', $settings)
            ? self::parseDays($settings['billing_reminder_before_days'], self::MAX_BEFORE)
            : self::DEFAULT_BEFORE_DAYS;

        $after = array_key_exists('billing_reminder_after_days', $settings)
            ? self::parseDays($settings['billing_reminder_after_days'], self::MAX_AFTER, 1)
            : self::legacyAfterDays($settings);

        rsort($before);
        sort($after);

        $time = substr((string) ($settings['billing_reminder_time'] ?? ''), 0, 5);

        return new self(
            beforeEnabled: self::truthy($settings['billing_reminder_before_enabled'] ?? false) && $before !== [],
            beforeDays: $before,
            beforeTemplateId: self::id($settings['billing_reminder_before_wa_template_id'] ?? null),
            afterEnabled: self::truthy($settings['billing_overdue_wa_enabled'] ?? false) && $after !== [],
            afterDays: $after,
            afterTemplateId: self::id($settings['billing_overdue_wa_template_id'] ?? null),
            time: preg_match('/^\d{2}:\d{2}$/', $time) ? $time : self::DEFAULT_TIME,
        );
    }

    /**
     * Pengaturan lama: "mulai H+X, maksimal N kali" => [X, X+1, ..., X+N-1].
     */
    private static function legacyAfterDays(array $settings): array
    {
        $start = max(1, (int) ($settings['billing_overdue_days_after_due'] ?? 1));
        $max = max(1, min(10, (int) ($settings['billing_overdue_max_reminders'] ?? 3)));

        return range($start, $start + $max - 1);
    }

    /**
     * Terima JSON "[3,1,0]", "3,1,0", atau array. Hasil: angka unik dalam batas.
     */
    public static function parseDays($value, int $max, int $min = 0): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }

        return collect(is_array($value) ? $value : [])
            ->filter(fn ($day) => is_numeric(trim((string) $day)))
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= $min && $day <= $max)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Tahap H- yang berlaku hari ini, atau null.
     * $daysUntilDue: 3 = jatuh tempo 3 hari lagi, 0 = hari ini.
     */
    public function beforeStage(int $daysUntilDue): ?int
    {
        if (!$this->beforeEnabled || $daysUntilDue < 0) {
            return null;
        }

        $reached = array_filter($this->beforeDays, fn (int $day) => $day >= $daysUntilDue);

        return $reached === [] ? null : min($reached);
    }

    /**
     * Tahap H+ yang berlaku hari ini, atau null. $daysOverdue: 1 = lewat 1 hari.
     */
    public function afterStage(int $daysOverdue): ?int
    {
        if (!$this->afterEnabled || $daysOverdue < 1) {
            return null;
        }

        $reached = array_filter($this->afterDays, fn (int $day) => $day <= $daysOverdue);

        return $reached === [] ? null : max($reached);
    }

    public static function label(string $type, int $days): string
    {
        if ($type === 'before') {
            return $days === 0 ? 'Hari H' : 'H-' . $days;
        }

        return 'H+' . $days;
    }

    private static function id($value): ?int
    {
        return (int) $value > 0 ? (int) $value : null;
    }

    private static function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
