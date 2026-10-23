{{--
    Contoh pesan WA + editor "pesan bawaan" untuk halaman Otomatis Tagihan.
    $type: generated | before | after | arrears | promise
    $title: judul kecil di atas contoh pesan
    $time: jam pada gelembung pesan
    $compact: true = contoh pesan baru tampil saat editor dibuka
--}}
@php
    $messageKey = 'billing_message_' . $type;
    $messageValue = old($messageKey, $settings[$messageKey]);
    $isCustom = trim(str_replace("\r\n", "\n", $messageValue)) !== $originalMessages[$type];
    $compact = $compact ?? false;
@endphp

<div class="ba-chat" data-message-box="{{ $type }}">
    @unless($compact)
        <div class="ba-chat-head"><span>{{ $title }}</span><span data-preview-name="{{ $type }}"></span></div>
        <div class="ba-bubble"><span data-preview-text="{{ $type }}"></span><small>{{ $time }}</small></div>
    @endunless

    <details class="ba-edit" @if($errors->has($messageKey) || ($compact && $isCustom)) open @endif>
        <summary>
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.2 5.2l3.6 3.6M4 20h4L18.6 9.4a2.5 2.5 0 00-3.6-3.6L4 16.4V20z"/></svg>
            <span>{{ $compact ? $title : 'Ubah pesan bawaan' }}</span>
            <span class="ba-tag ml-auto" style="--tone: var(--ba-amber)" data-custom-badge="{{ $type }}" @unless($isCustom) hidden @endunless>Diubah</span>
        </summary>

        <textarea name="{{ $messageKey }}" rows="9" class="ba-field ba-textarea mt-2 @error($messageKey) ba-invalid @enderror" data-message="{{ $type }}" spellcheck="false">{{ $messageValue }}</textarea>
        @error($messageKey)<div class="ba-error">{{ $message }}</div>@enderror

        @if($compact)
            <div class="ba-bubble mt-2"><span data-preview-text="{{ $type }}"></span><small>{{ $time }}</small></div>
        @endif

        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <span class="ba-hint !mt-0">Dipakai saat template WA = <b>Pesan bawaan</b>. Daftar kode isian ada di kartu "Kode isian template".</span>
            <button type="button" class="text-[11px] font-bold hover:underline" style="color: var(--ba-primary)" data-reset-message="{{ $type }}">Kembalikan teks asli</button>
        </div>
    </details>
</div>
