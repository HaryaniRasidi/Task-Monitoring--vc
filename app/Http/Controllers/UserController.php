<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
class UserController extends Controller {
    public function index(Request $request) {
        abort_unless($request->user()->role === 'supervisor', 403);
        return view('users.index', ['officers' => User::where('role', 'officer')->orderBy('name')->get()]);
    }
    public function store(Request $request) {
        abort_unless($request->user()->role === 'supervisor', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'max:200', Password::min(12)->letters()->numbers(), 'confirmed'],
            'password_confirmation' => ['required', 'string', 'max:200'],
        ]);
        unset($data['password_confirmation']);
        $user = new User($data);
        $user->role = 'officer';
        $user->save();
        return redirect()->route('users.index')->with('success', __('Officer added successfully.'));
    }
}