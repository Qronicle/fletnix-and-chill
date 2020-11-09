<?php
/**
 * TetrusController.php
 */

namespace App\Http\Controllers\Picturnery;

use App\Http\Controllers\Controller;

/**
 * TetrusController
 *
 * @author  Ruud Seberechts
 * @package App\Http\Controllers\Picturnery
 * @since   2020-04-13 17:24
 */
class TetrusController extends Controller
{
    public function index()
    {
        return view('picturnery.rooms.tetrus');
    }
}