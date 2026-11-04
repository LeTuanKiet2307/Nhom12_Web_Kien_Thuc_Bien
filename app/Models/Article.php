<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = ['title', 'content', 'wiki_page_id', 'source_name', 'source_url', 'license'];

    public function quizSets()
    {
        return $this->hasMany(QuizSet::class);
    }

    public function isLocked(): bool
    {
        return $this->quizSets()->where('is_active', true)->exists() || $this->quizSets()->whereHas('attempts')->exists();
    }
}
