<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mail extends Model
{
    use HasFactory;

    protected $table = 'mail';
    public $timestamps = false; // The original table doesn't have timestamps

    protected $fillable = [
        'text'
    ];

    /**
     * Get the latest mail text.
     */
    public static function getLatestText()
    {
        $mail = static::latest('id')->first();
        return $mail ? $mail->text : null;
    }

    /**
     * Update or create mail text.
     */
    public static function updateText($text)
    {
        // Clear existing records and create new one
        static::truncate();
        return static::create(['text' => $text]);
    }
}
