<?php

namespace App\Http\Controllers;

use App\Models\AdditionalUser;
use App\Models\Company;
use App\Models\User;
use App\Models\UserMetadata;
use App\Models\VirtualUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    private array $userFields = [
        'companyId', 'isActive', 'isAdmin', 'isHidden',
        'permission_loading', 'permission_unloading',
        'permission_shellTesting', 'permission_lookup',
    ];

    /**
     * List all users.
     */
    public function index()
    {
        // Standard users (joined with userMetadata for permissions)
        $standard = DB::table('users as u')
            ->leftJoin('userMetadata as m', 'u.id', '=', 'm.id')
            ->where('u.isHidden', 0)
            ->select(
                'u.id', 'u.username', 'u.nameFirst', 'u.nameLast',
                DB::raw('COALESCE(m.companyId, 0) as companyId'),
                DB::raw('COALESCE(m.isActive, 0) as isActive'),
                DB::raw('COALESCE(m.isAdmin, 0) as isAdmin'),
                DB::raw('COALESCE(m.isHidden, 0) as isHidden'),
                DB::raw('COALESCE(m.permission_loading, 0) as permission_loading'),
                DB::raw('COALESCE(m.permission_unloading, 0) as permission_unloading'),
                DB::raw('COALESCE(m.permission_shellTesting, 0) as permission_shellTesting'),
                DB::raw('COALESCE(m.permission_lookup, 0) as permission_lookup')
            )
            ->orderBy('u.nameLast')->orderBy('u.nameFirst')
            ->get()->map(fn($r) => (array)$r);

        // Additional (valve floor) users
        $additional = AdditionalUser::orderBy('nameLast')->orderBy('nameFirst')
            ->get()->map(fn($r) => $r->toArray());

        // Virtual users
        $virtual = VirtualUser::orderBy('nameLast')->orderBy('nameFirst')
            ->get()->map(fn($r) => array_merge(
                ['username' => '', 'isAdmin' => 0, 'isHidden' => 0,
                 'permission_loading' => 0, 'permission_unloading' => 0,
                 'permission_shellTesting' => 0, 'permission_lookup' => 0],
                $r->toArray()
            ));

        $records = $standard->concat($additional)->concat($virtual);

        return view('users.index', [
            'records' => $records,
            'companies' => Company::orderBy('name')->get(),
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * Edit form for a valve app user.
     */
    public function edit(Request $request, ?int $id = null)
    {
        $companies = Company::orderBy('name')->get();

        if ($id) {
            $record = User::findOrFail($id);
            $title = "Edit User - $record->nameFirst $record->nameLast";
        } else {
            $record = null;
            $title = "New User";
            // Available master users not yet in the system
            $existingIds = UserMetadata::pluck('id')->toArray();
            $availableUsers = User::whereNotIn('id', $existingIds)->orderBy('nameLast')->get();
        }

        return view('users.edit', [
            'record' => $record,
            'title' => $title,
            'companies' => $companies,
            'availableUsers' => $availableUsers ?? collect(),
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * Save a valve app user.
     */
    public function save(Request $request)
    {
        $data = $request->only($this->userFields);

        // Default checkboxes to 0 if not present
        foreach (['isActive','isAdmin','isHidden','permission_loading','permission_unloading','permission_shellTesting','permission_lookup'] as $cb) {
            $data[$cb] = $request->has($cb) ? 1 : 0;
        }

        $id = $request->input('id');

        if ($id) {
            UserMetadata::where('id', $id)->update($data);
        } else {
            $data['id'] = $request->input('newId');
            UserMetadata::create($data);
        }

        return redirect()->route('users.index')
            ->with('message_success', 'User saved successfully.');
    }

    /**
     * Edit form for an Additional User (factory floor users with their own login).
     */
    public function editAdditional(?int $id = null)
    {
        if ($id) {
            $user = User::findOrFail($id);
            $additional = AdditionalUser::find($id);
            $title = "Edit Additional User - $user->nameFirst $user->nameLast";
        } else {
            $user = null;
            $additional = null;
            $title = "New Additional User";
        }

        return view('users.edit-additional', [
            'record' => $user,
            'additionalRecord' => $additional,
            'title' => $title,
            'companies' => Company::orderBy('name')->get(),
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * Save an Additional User.
     */
    public function saveAdditional(Request $request)
    {
        $request->validate([
            'nameFirst' => 'required',
            'nameLast' => 'required',
            'username' => 'required',
        ]);

        $id = $request->input('id');
        $additionalData = $request->only(['nameFirst', 'nameLast', 'username']);
        $metaData = $request->only($this->userFields);
        foreach (['isActive','isAdmin','isHidden','permission_loading','permission_unloading','permission_shellTesting','permission_lookup'] as $cb) {
            $metaData[$cb] = $request->has($cb) ? 1 : 0;
        }

        if ($id) {
            AdditionalUser::where('id', $id)->update($additionalData);
            UserMetadata::where('id', $id)->update($metaData);
        } else {
            $newId = AdditionalUser::insertGetId($additionalData);
            $metaData['id'] = $newId;
            UserMetadata::create($metaData);
        }

        return redirect()->route('users.index')
            ->with('message_success', 'Additional user saved successfully.');
    }

    /**
     * Edit form for a Virtual User (display names for loaded/unloaded by dropdowns).
     */
    public function editVirtual(?int $id = null)
    {
        if ($id) {
            $virtualUser = VirtualUser::findOrFail($id);
            $meta = UserMetadata::find($id);
            $title = "Edit Virtual User - $virtualUser->nameFirst $virtualUser->nameLast";
        } else {
            $virtualUser = null;
            $meta = null;
            $title = "New Virtual User";
        }

        return view('users.edit-virtual', [
            'record' => $virtualUser,
            'meta' => $meta,
            'title' => $title,
            'companies' => Company::orderBy('name')->get(),
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * Save a Virtual User.
     */
    public function saveVirtual(Request $request)
    {
        $request->validate([
            'nameFirst' => 'required',
            'nameLast' => 'required',
        ]);

        $id = $request->input('id');
        $virtualData = $request->only(['nameFirst', 'nameLast']);
        $metaData = ['companyId' => $request->input('companyId', 0), 'isActive' => $request->has('isActive') ? 1 : 0];

        if ($id) {
            VirtualUser::where('id', $id)->update($virtualData);
            UserMetadata::where('id', $id)->update($metaData);
        } else {
            $newId = VirtualUser::insertGetId($virtualData);
            $metaData['id'] = $newId;
            UserMetadata::create($metaData);
        }

        return redirect()->route('users.index')
            ->with('message_success', 'Virtual user saved successfully.');
    }
}
