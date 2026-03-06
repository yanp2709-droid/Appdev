use App\Models\User;

Route::get('/test-db', function() {
    // Create a test user
    $user = new User;
    $user->name = 'Test User';
    $user->email = 'test@example.com';
    $user->password = bcrypt('password');
    $user->save();

    // Return all users
    return User::all();
});