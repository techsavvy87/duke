<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Service;
use App\Models\DemoAccount;

class UserController extends Controller
{
    public function listUsers(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        if (!empty($search)) {
            $users = User::where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number_1', 'like', "%{$search}%")
                            ->orWhere('phone_number_2', 'like', "%{$search}%");
                    });
            })->whereDoesntHave('roles', function ($query) {
                $query->where('title', 'customer');
            })->when(auth()->user()->isDemo(), fn ($query) => $this->excludeOwners($query))->paginate($perPage);
        } else {
            $users = User::whereDoesntHave('roles', function ($query) {
                        $query->where('title', 'customer');
                    })->when(auth()->user()->isDemo(), fn ($query) => $this->excludeOwners($query))->paginate($perPage);
        }
        return view('users.index', compact('search', 'users'));
    }

    public function addUser()
    {
        $roles = $this->assignableRoles();
        // Logic to show the user creation form
        return view('users.create', compact('roles'));
    }

    public function processFileUpload(Request $request)
    {
        try {
            $request->validate([
                'avatar_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            ]);

            // Handle file upload logic here
            $file = $request->file('avatar_img');

            // generate a unique file name
            $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();

            // Store in temporary directory
            $path = $file->storeAs('temp', $fileName, 'local');

            return response()->json([
                'temp_file' => $fileName,
                'original_name' => $file->getClientOriginalName(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'File upload failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function revertFileUpload(Request $request)
    {
        try {
            $tempFile = $request->getContent();

            if ($tempFile && Storage::disk('local')->exists('temp/' . $tempFile)) {
                Storage::disk('local')->delete('temp/' . $tempFile);
            }
            return response()->json(['message' => 'File reverted successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'File deletion failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number_1' => 'required|string',
            'temp_file' => 'nullable|string'
        ]);
        $this->ensureAssignableRoles($request->roles);

        // Create account
        $user = new User();
        $user->name = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);

        $isEmailVerified = $request->boolean('email_verified') ?? false;
        if ($isEmailVerified) {
            $user->email_verified_at = Carbon::now();
        }
        $user->status = $request->boolean('is_active') ?? false;
        $user->block_reservations = $request->boolean('block_reservations') ?? false;
        $user->block_messages = $request->boolean('block_messages') ?? false;

        $user->save();

        // Create profile
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->first_name = $request->first_name;
        $profile->last_name = $request->last_name;
        $profile->phone_number_1 = $request->phone_number_1;
        $profile->phone_number_2 = $request->phone_number_2;
        $profile->address = $request->street_address;
        $profile->city = $request->city;
        $profile->state = $request->state;
        $profile->zip_code = $request->zip_code;
        $profile->emergency_contact_info = $request->emergency_contact_info;
        $profile->home_number = $request->home_number;
        $profile->work_number = $request->work_number;

        if ($request->filled('temp_file')) {
            $tempFile = $request->temp_file;
            $tempPath = 'temp/' . $tempFile;

            if (Storage::disk('local')->exists($tempPath)) {
                // Get file contents and ensure it's not null
                $fileContents = Storage::disk('local')->get($tempPath);

                if ($fileContents !== null) {
                    // Move the file to a permanent location
                    $permanentPath = 'profiles/' . $tempFile;
                    Storage::disk('public')->put($permanentPath, $fileContents);
                    Storage::disk('local')->delete($tempPath); // Delete the temporary file
                }
            }
            $profile->avatar_img = $tempFile; // Store the file name in the profile
        }

        $profile->save();

        // Assign role if provided
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        // Optionally, redirect or return a response
        return redirect()->route('users')->with([
            'status' => 'success',
            'message' => 'User created successfully!'
        ]);
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->ensureManageableUser($user);
        $roles = $this->assignableRoles();

        return view('users.update', compact('user', 'roles'));
    }

    public function updateUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'username' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $request->user_id,
            'password' => 'nullable|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number_1' => 'required|string',
            'avatar_action' => 'required|in:keep,change,delete',
            'temp_file' => 'nullable|string',
            'current_avatar' => 'nullable|string',
        ]);
        $this->ensureAssignableRoles($request->roles);

        $user = User::findOrFail($request->user_id);
        $this->ensureManageableUser($user);

        // Update user account information
        $user->name = $request->username;
        $user->email = $request->email;
        // Update password only if provided
        if ($request->password) {
            $user->password = bcrypt($request->password);
        }
        $isEmailVerified = $request->boolean('email_verified') ?? false;
        if ($isEmailVerified) {
            $user->email_verified_at = Carbon::now();
        } else {
            $user->email_verified_at = null;
        }
        $user->status = $request->boolean('is_active') ?? false;
        $user->block_reservations = $request->boolean('block_reservations') ?? false;
        $user->block_messages = $request->boolean('block_messages') ?? false;

        $user->save();

        // Update profile information
        $profile = $user->profile;
        if (!$profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;
        }

        $profile->first_name = $request->first_name;
        $profile->last_name = $request->last_name;
        $profile->phone_number_1 = $request->phone_number_1;
        $profile->phone_number_2 = $request->phone_number_2;
        $profile->address = $request->street_address;
        $profile->city = $request->city;
        $profile->state = $request->state;
        $profile->zip_code = $request->zip_code;
        $profile->emergency_contact_info = $request->emergency_contact_info;
        $profile->home_number = $request->home_number;
        $profile->work_number = $request->work_number;

        // Handle avatar based on action
        switch ($request->avatar_action) {
            case 'keep':
                // Do nothing - keep the current avatar
                break;

            case 'change':
                // Delete old avatar if exists
                if ($profile->avatar_img) {
                    $oldAvatarPath = 'profiles/' . $profile->avatar_img;
                    if (Storage::disk('public')->exists($oldAvatarPath)) {
                        Storage::disk('public')->delete($oldAvatarPath);
                    }
                }

                // Move new avatar from temp to permanent location
                if ($request->temp_file) {
                    $tempFile = $request->temp_file;
                    $tempPath = 'temp/' . $tempFile;

                    if (Storage::disk('local')->exists($tempPath)) {
                        $permanentPath = 'profiles/' . $tempFile;
                        Storage::disk('public')->put($permanentPath, Storage::disk('local')->get($tempPath));
                        Storage::disk('local')->delete($tempPath);
                        $profile->avatar_img = $tempFile;
                    }
                }
                break;

            case 'delete':
                // Delete current avatar
                if ($profile->avatar_img) {
                    $avatarPath = 'profiles/' . $profile->avatar_img;
                    if (Storage::disk('public')->exists($avatarPath)) {
                        Storage::disk('public')->delete($avatarPath);
                    }
                    $profile->avatar_img = null;
                }
                break;
        }

        $profile->save();

        // Update roles
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return redirect()->route('users')->with([
            'status' => 'success',
            'message' => 'User updated successfully!'
        ]);
    }

    public function deleteUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $this->ensureManageableUser($user);

        // Prevent deletion of the currently logged-in user
        if ($user->id === auth()->id()) {
            return redirect()->route('users')->with([
                'status' => 'fail',
                'message' => 'You cannot delete your own account!'
            ]);
        }

        // Delete user's avatar if exists
        if ($user->profile && $user->profile->avatar_img) {
            $avatarPath = 'profiles/' . $user->profile->avatar_img;
            if (Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }
        }

        // Delete the user (this will cascade delete profile and role relationships)
        $user->delete();

        return redirect()->route('users')->with([
            'status' => 'success',
            'message' => 'User deleted successfully!'
        ]);
    }

    /**
     * Roles the authenticated user may assign. Demo users never get the Owner role.
     */
    private function assignableRoles()
    {
        return Role::when(auth()->user()->isDemo(), function ($query) {
            $query->whereRaw('LOWER(title) != ?', ['owner']);
        })->get();
    }

    private function ensureAssignableRoles($roleIds): void
    {
        if (!auth()->user()->isDemo() || empty($roleIds)) {
            return;
        }

        $assignsOwner = Role::whereIn('id', (array) $roleIds)
            ->whereRaw('LOWER(title) = ?', ['owner'])
            ->exists();

        if ($assignsOwner) {
            throw ValidationException::withMessages([
                'roles' => 'You are not allowed to assign the Owner role.',
            ]);
        }
    }

    private function excludeOwners($query)
    {
        return $query->whereDoesntHave('roles', function ($q) {
            $q->whereRaw('LOWER(title) = ?', ['owner']);
        });
    }

    /**
     * Demo users can never view or change an Owner (Super Admin) account.
     */
    private function ensureManageableUser(User $user): void
    {
        if (auth()->user()->isDemo() && $user->roles->contains(fn ($role) => strtolower($role->title) === 'owner')) {
            abort(404);
        }
    }

    /*
     * Demo Account Management (Super Admin only)
     */

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
    }

    /**
     * Existing non-owner role used for the demo login.
     */
    private function demoAccountRole(): ?Role
    {
        return Role::whereRaw('LOWER(title) = ?', ['manager'])->first()
            ?? Role::whereRaw('LOWER(title) NOT IN (?, ?)', ['owner', 'customer'])->orderBy('id')->first();
    }

    private function demoAccountRules(?DemoAccount $demoAccount = null): array
    {
        return [
            'facility_name' => 'required|string|max:255',
            'services' => 'nullable|array',
            'services.*' => [
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where('level', 'primary'),
            ],
            'username' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($demoAccount?->user_id),
            ],
            'password' => $demoAccount ? 'nullable|string|confirmed' : 'required|string|confirmed',
            'status' => 'required|in:active,inactive',
        ];
    }

    private function saveDemoAccountUser(User $user, DemoAccount $demoAccount, Request $request): void
    {
        $user->name = $request->username;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        $user->email_verified_at = $user->email_verified_at ?? Carbon::now();
        $user->status = true;
        $user->demo_account_id = $demoAccount->id;
        $user->save();

        $nameParts = preg_split('/\s+/', trim($request->username), 2);
        $profile = Profile::where('user_id', $user->id)->first() ?? new Profile();
        $profile->user_id = $user->id;
        $profile->first_name = $nameParts[0] ?? '';
        $profile->last_name = $nameParts[1] ?? '';
        $profile->phone_number_1 = $profile->phone_number_1 ?? '';
        $profile->save();
    }

    public function listDemoAccounts(Request $request)
    {
        $this->ensureSuperAdmin();

        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');

        $demoAccounts = DemoAccount::with(['user', 'enabledServices'])
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('facility_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('demo-accounts.index', compact('demoAccounts', 'search'));
    }

    /**
     * Every primary service, loaded from the database (never hardcoded).
     */
    private function demoAccountServices()
    {
        return Service::where('level', 'primary')->orderBy('name')->get();
    }

    public function addDemoAccount()
    {
        $this->ensureSuperAdmin();

        $services = $this->demoAccountServices();

        return view('demo-accounts.create', compact('services'));
    }

    public function editDemoAccount($id)
    {
        $this->ensureSuperAdmin();

        $demoAccount = DemoAccount::with(['user', 'enabledServices'])->findOrFail($id);
        $services = $this->demoAccountServices();

        return view('demo-accounts.update', compact('demoAccount', 'services'));
    }

    public function createDemoAccount(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate($this->demoAccountRules());

        $role = $this->demoAccountRole();
        if (!$role) {
            return redirect()->back()->withInput()->with([
                'status' => 'fail',
                'message' => 'No non-owner role is available for the demo login.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $role) {
                $demoAccount = new DemoAccount();
                $demoAccount->facility_name = $request->facility_name;
                $demoAccount->status = $request->status;
                $demoAccount->save();

                $user = new User();
                $this->saveDemoAccountUser($user, $demoAccount, $request);
                $user->roles()->sync([$role->id]);

                $demoAccount->user_id = $user->id;
                $demoAccount->save();
                $demoAccount->services()->sync($request->input('services', []));
                $demoAccount->syncServiceCopies();
            });
        } catch (Exception $e) {
            Log::error('Demo account creation failed: ' . $e->getMessage());

            return redirect()->back()->withInput()->with([
                'status' => 'fail',
                'message' => 'Failed to create the demo account. Please try again.',
            ]);
        }

        return redirect()->route('demo-accounts')->with([
            'status' => 'success',
            'message' => 'Demo account created successfully!',
        ]);
    }

    public function updateDemoAccount(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate(['demo_account_id' => 'required|exists:demo_accounts,id']);

        $demoAccount = DemoAccount::findOrFail($request->demo_account_id);
        $request->validate($this->demoAccountRules($demoAccount));

        $user = $demoAccount->user ?? new User();
        if (!$user->exists && !$request->filled('password')) {
            throw ValidationException::withMessages([
                'password' => 'A password is required because this demo account has no login yet.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $demoAccount, $user) {
                $isNewUser = !$user->exists;

                $demoAccount->facility_name = $request->facility_name;
                $demoAccount->status = $request->status;

                $this->saveDemoAccountUser($user, $demoAccount, $request);
                if ($isNewUser && ($role = $this->demoAccountRole())) {
                    $user->roles()->sync([$role->id]);
                }

                $demoAccount->user_id = $user->id;
                $demoAccount->save();
                $demoAccount->services()->sync($request->input('services', []));
                $demoAccount->syncServiceCopies();
            });
        } catch (Exception $e) {
            Log::error('Demo account update failed: ' . $e->getMessage());

            return redirect()->back()->withInput()->with([
                'status' => 'fail',
                'message' => 'Failed to update the demo account. Please try again.',
            ]);
        }

        return redirect()->route('demo-accounts')->with([
            'status' => 'success',
            'message' => 'Demo account updated successfully!',
        ]);
    }

    public function deleteDemoAccount(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'demo_account_id' => 'required|exists:demo_accounts,id',
        ]);

        $demoAccount = DemoAccount::findOrFail($request->demo_account_id);

        try {
            $demoAccount->deleteWithEnvironment();
        } catch (Exception $e) {
            Log::error('Demo account deletion failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'fail',
                'message' => 'Failed to delete the demo account. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Demo account deleted successfully!',
        ]);
    }

    public function updateDemoAccountStatus(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'demo_account_id' => 'required|exists:demo_accounts,id',
            'status' => 'required|in:active,inactive',
        ]);

        $demoAccount = DemoAccount::findOrFail($request->demo_account_id);
        $demoAccount->status = $request->status;
        $demoAccount->save();

        return response()->json([
            'status' => 'success',
            'message' => $request->status === 'active'
                ? 'Demo account activated successfully!'
                : 'Demo account deactivated successfully!',
        ]);
    }
}
