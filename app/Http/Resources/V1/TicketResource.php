<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'status' => $this->status,
            'priority' => $this->priority,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'replies' => $this->whenLoaded('replies', fn () => $this->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'user_id' => $reply->user_id,
                'message' => $reply->message,
                'attachment_url' => $reply->attachment_path ? asset('storage/' . $reply->attachment_path) : null,
                'user' => $reply->relationLoaded('user') && $reply->user ? [
                    'id' => $reply->user->id,
                    'name' => $reply->user->name,
                    'role' => $reply->user->role,
                ] : null,
                'created_at' => optional($reply->created_at)->toISOString(),
            ])),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
