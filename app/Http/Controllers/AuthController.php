<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function create() { return view('auth.login'); }
    public function store(Request $request) {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254'], 'password' => ['required', 'string', 'max:200']]);
        if (!Auth::attempt($data)) { throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]); }
        $request->session()->regenerate();
        $dashboardAllowed = \Illuminate\Support\Facades\Gate::allows('view-dashboard');
        // Do not return an officer to a dashboard URL captured while logged out.
        $intended = $request->session()->get('url.intended');
        if (!$dashboardAllowed && $intended && rtrim((string) parse_url($intended, PHP_URL_PATH), '/') === '/dashboard') {
            $request->session()->forget('url.intended');
        }
        return redirect()->intended(route($dashboardAllowed ? 'dashboard' : 'tasks.index'));
    }
    public function destroy(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
