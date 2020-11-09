<?php
/**
 * RoomController.php
 */

namespace App\Http\Controllers;

use App\WebSocket\User;
use Illuminate\Http\Request;

/**
 * RoomController
 *
 * @author  Ruud Seberechts
 * @package App\Http\Controllers
 * @since   2020-03-25 15:45
 */
class RoomController extends Controller
{
    /** @var User */
    protected $user;

    public function login()
    {
        if ($this->getUser()->id) {
            return redirect()->route('index');
        }
        return view('login');
    }

    public function loginPost(Request $request)
    {
        if ($this->getUser()->id) {
            return redirect()->route('index');
        }
        $request->validate([
            'username' => 'required|string',
        ]);
        User::create(trim($request->post('username')));
        return redirect()->route('index');
    }

    public function index()
    {
        if (!$this->getUser()->id) {
            return redirect()->route('login');
        }
        return view('index', [
            'user' => $this->getUser(),
        ]);
    }

    public function room($roomType, $roomId)
    {
        if (!$this->getUser()->id) {
            return redirect()->route('login');
        }
        return view('rooms.' . $roomType, [
            'user'   => $this->getUser(),
            'roomId' => $roomId,
        ]);
    }

    protected function getUser(): User
    {
        if (is_null($this->user)) {
            $this->user = User::fromSession();
        }
        return $this->user;
    }
}
