<?php
/**
 * Category.php
 */

namespace App\WebSocket\Words;

use Illuminate\Database\Eloquent\Model;

/**
 * Category
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket\Words
 * @since   2020-03-28 18:19
 */
class Category extends Model
{
    protected $table = 'picturnery_word_categories';
    public $timestamps = false;

    public function categories()
    {
        return $this->belongsToMany('App\WebSocket\Words\Word', 'picturnery_words_x_categories');
    }
}