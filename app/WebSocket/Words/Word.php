<?php
/**
 * Word.php
 */

namespace App\WebSocket\Words;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Word
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket\Words
 * @since   2020-03-28 18:18
 */
class Word extends Model
{
    protected $table = 'picturnery_words';
    public $timestamps = false;

    public function categories()
    {
        return $this->belongsToMany('App\WebSocket\Words\Category', 'picturnery_words_x_categories');
    }

    public static function getRandom($amount = 3)
    {
        $numWords = Word::where('locale', 'nl_NL')->count();
        $words = [];
        for ($w = 0; $w < $amount; $w++) {
            $num = rand(0, $numWords - 1);
            do {
                $word = Word::where('locale', 'nl_NL')->skip($num)->take(1)->first();
            } while (isset($words[$word->id]));
            $words[$word->id] = $word->name;
        }
        return $words;
    }
}