<?php
/**
 * Created by PhpStorm.
 * User: Sorarinu
 * Date: 2016/09/19
 * Time: 20:30
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maknz\Slack\Facades\Slack;

class ApiController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function signUp()
    {
        $errors = '';

        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ];

            $validation = \Validator::make($this->request->all(), $rules);

            if ($validation->passes()) {
                $data = json_decode(json_encode([
                    'password' => $this->request->input('password'),
                    'email' => $this->request->input('email')
                ]));

                $user = new User;
                $user->email = $data->email;
                $user->password = Hash::make($data->password);
                $user->save();

                Slack::send('New user has been created！ This Email Address is *' . $data->email . '*.');
                return response()->json(['status' => 'OK', 'message' => $data->email . ' created.']);
            }

            foreach ($validation->errors()->all() as $error) {
                $errors .= $error;
            }

            return response()->json(['status' => 'NG', 'message' => $errors]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'NG',
                'message' => $e->getCode() === '23000' ? 'This Email address is already registered.' : $e->getMessage()]);
        }
    }

    public function signIn()
    {
        $errors = '';

        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ];

            $validation = \Validator::make($this->request->all(), $rules);

            if ($validation->passes()) {
                $data = json_decode(json_encode([
                    'password' => $this->request->input('password'),
                    'email' => $this->request->input('email')
                ]));

                $user = User::where('email', $data->email)
                    ->firstOrFail();

                if (Hash::check($data->password, $user->password)) {
                    $this->request->session()->put('email', $user->email);
                    return response()->json(['status' => 'OK', 'message' => 'Login success: ' . $user->email]);
                }

                throw new \Exception;
            }

            foreach ($validation->errors()->all() as $error) {
                $errors .= $error;
            }

            return response()->json(['status' => 'NG', 'message' => $errors]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'NG', 'message' => 'User Login is Failed. ' . $e->getMessage()]);
        }
    }

    public function signOut()
    {
        if ($this->request->session()->has('email')) {
            $this->request->session()->forget('email');
            return response()->json(['status' => 'OK', 'message' => 'Logged out.']);
        }

        return response()->json(['status' => 'NG', 'message' => 'This user is not authenticated.']);
    }

    public function upload()
    {
    }

    public function export()
    {
    }

    public function create()
    {
    }

    public function update()
    {
    }

    public function getAll()
    {
    }
}