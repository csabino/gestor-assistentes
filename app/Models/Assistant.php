<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assistant extends Model
{
    protected $fillable = [
        'name', 'is_active', 'provider', 'system_prompt', 'model', 
        'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key', 
        'knowledge_files',
        'whatsapp_provider', 'whatsapp_url', 'whatsapp_instance', 'whatsapp_token', 'whatsapp_verify_token'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'knowledge_files' => 'array',
        'openai_api_key' => 'encrypted',
        'gemini_api_key' => 'encrypted',
        'anthropic_api_key' => 'encrypted',
        'grok_api_key' => 'encrypted',
        'whatsapp_token' => 'encrypted', // Criptografa o token do WhatsApp no banco
    ];
}