<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assistant extends Model
{
    protected $fillable = [
        'name', 'is_active', 'provider', 'system_prompt', 'model', 
        'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key', 
        'knowledge_files'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'knowledge_files' => 'array',
        'openai_api_key' => 'encrypted',
        'gemini_api_key' => 'encrypted',
        'anthropic_api_key' => 'encrypted',
        'grok_api_key' => 'encrypted',
    ];
}