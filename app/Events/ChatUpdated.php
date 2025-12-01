<?php

namespace App\Events;

use App\Models\contacts\api;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChatUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;
    public $otherUserId;

    public function __construct($conversationId, $userId, $otherUserId)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->otherUserId = $otherUserId;
    }

    public function broadcastOn()
    {
        return new Channel('chat-updates.' . $this->userId);
    }

    public function broadcastWith()
    {
        $conversation = Conversation::with([
            'messages' => fn($query) => $query->latest()->limit(1),
            'user1.roles', 'user2.roles',
            'user1.professor', 'user2.professor',
            'user1.student', 'user2.student',
        ])->find($this->conversationId);

        $otherUser = $this->otherUserId == $conversation->user1->id
            ? $conversation->user1
            : $conversation->user2;

        $lastMessage = $conversation->messages->last();

        $profile = null;
        if ($otherUser->roles->contains('name', 'professor')) {
            $profile = api::api . $otherUser->professor->profile;
        } elseif ($otherUser->roles->contains('name', 'student')) {
            $profile = api::api . $otherUser->student->profile;
        }
        $lastMessageText = match ($lastMessage->message_type) {
            'text' => $lastMessage->message,
            'file' => 'فایل',
            'voice' => 'فایل صوتی',
            default => 'پیام',
        };


        return [
            'id' => $otherUser->id,
            'user_name' => $otherUser->first_name,
            'user_image' => $profile,
            'last_message' => $lastMessageText,
            'last_message_time' => $lastMessage?->created_at->timezone('Asia/Tehran')->format('H:i'),
        ];
    }

    public function broadcastAs()
    {
        return 'chat.updated';
    }
}
