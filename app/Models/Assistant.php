<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assistant extends Model
{
    protected $fillable = [
        'name', 'provider', 'model', 'system_prompt',
        'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key',
        'whatsapp_provider', 'whatsapp_url', 'whatsapp_instance', 'whatsapp_token', 'whatsapp_verify_token',
        'knowledge_files', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Se tiver lixo binário, ele ignora e salva a tela de dar Erro 500
    public function getKnowledgeFilesAttribute($value)
    {
        if (empty($value)) return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setKnowledgeFilesAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['knowledge_files'] = null;
        } else {
            $this->attributes['knowledge_files'] = is_string($value) ? $value : json_encode($value, JSON_INVALID_UTF8_IGNORE);
        }
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}