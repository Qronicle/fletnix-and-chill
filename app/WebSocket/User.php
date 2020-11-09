<?php
/**
 * User.php
 */

namespace App\WebSocket;

use Illuminate\Database\Eloquent\Model;

/**
 * User
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket
 * @since   2020-03-28 11:06
 */
class User extends Model
{
    protected $table = 'guest_users';

    public static function create(string $username): User
    {
        $user = new User();
        $user->username = $username;
        $user->secret = md5(microtime(true) . rand(0, 10000));
        $user->save();
        session()->put('picturnery_user', $user->id);
        return $user;
    }

    public static function fromSession(): User
    {
        $userId = session()->get('picturnery_user');
        return User::find($userId) ?: new User();
    }

    public function __toString()
    {
        return $this->id . ' (' . $this->username . ')';
    }

}
