<?php

namespace App\Events;
use App\Models\contacts\api;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public $payload;


    public function __construct($payload)
    {
        $this->payload = $payload;
    }


    public function broadcastOn()
    {
        $user1 = min($this->payload['user1_id'], $this->payload['user2_id']);
        $user2 = max($this->payload['user1_id'], $this->payload['user2_id']);

        return new Channel('chat.' . $user1 . '-' . $user2);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        $fileType = null;

        if (!empty($this->payload['voice_path'])) {
            $fileType = 'audio';
        } elseif (!empty($this->payload['file_path'])) {
            $extension = strtolower(pathinfo($this->payload['file_path'], PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                $fileType = 'image';
            } elseif (in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac'])) {
                $fileType = 'audio';
            } elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {
                $fileType = 'video';
            } else {
                $fileType = 'file';
            }
        }

        return [
            'text' => $this->payload['message'] ?? null,
            'message_type' => $this->payload['message_type'],
            'file_path' => $this->payload['file_path'] ? api::api . $this->payload['file_path'] : null,
            'voice_path' => $this->payload['voice_path'] ? api::api . $this->payload['voice_path'] : null,
            'file_type' => $fileType,
            'sender_id' => $this->payload['sender_id'],
            'conversation_id' => $this->payload['conversation_id'],
            'sent_at' => now()->format('Y-m-d H:i:s'),
        ];
    }





}
