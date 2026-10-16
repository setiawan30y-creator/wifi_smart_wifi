<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment_path'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
