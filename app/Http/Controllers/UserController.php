<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        // Verificar se usuário tem permissão
        $authUser = Auth::user();
        if (!in_array($authUser->role, ['admin', 'gerente'])) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $users = User::orderBy('name')->paginate(15);
        return view('users.index', compact('users'));
    }
    
    public function create()
    {
        // Verificar se usuário tem permissão
        $authUser = Auth::user();
        if (!in_array($authUser->role, ['admin', 'gerente'])) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $roles = [
            'admin' => 'Administrador',
            'gerente' => 'Gerente',
            'garcom' => 'Garçom/Garçonete',
            'caixa' => 'Caixa',
            'cozinha' => 'Cozinha',
        ];
        
        return view('users.create', compact('roles'));
    }
    
    public function store(Request $request)
    {
        // Verificar se usuário tem permissão
        $authUser = Auth::user();
        if (!in_array($authUser->role, ['admin', 'gerente'])) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,gerente,garcom,caixa,cozinha',
            'active' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14|unique:users',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
        ]);
        
        // Restrição: gerente não pode criar admin
        if ($authUser->role === 'gerente' && $validated['role'] === 'admin') {
            return back()->withErrors([
                'role' => 'Gerentes não podem criar administradores.'
            ])->withInput();
        }
        
        $validated['password'] = Hash::make($validated['password']);
        $validated['active'] = $request->has('active');
        
        User::create($validated);
        
        return redirect()->route('users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }
    
    public function show(User $user)
    {
        $authUser = Auth::user();
        
        // Usuário pode ver seu próprio perfil ou admin/gerente pode ver qualquer um
        if ($authUser->id !== $user->id && !in_array($authUser->role, ['admin', 'gerente'])) {
            abort(403, 'Acesso não autorizado.');
        }
        
        // Gerente não pode ver detalhes de admin
        if ($authUser->role === 'gerente' && $user->role === 'admin') {
            abort(403, 'Acesso não autorizado.');
        }
        
        return view('users.show', compact('user'));
    }
    
    public function edit(User $user)
    {
        $authUser = Auth::user();
        
        // Verificar permissões
        $canEdit = false;
        if ($authUser->role === 'admin') {
            $canEdit = true;
        } elseif ($authUser->role === 'gerente' && $user->role !== 'admin') {
            $canEdit = true;
        } elseif ($authUser->id === $user->id) {
            $canEdit = true;
        }
        
        if (!$canEdit) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $roles = [
            'admin' => 'Administrador',
            'gerente' => 'Gerente',
            'garcom' => 'Garçom/Garçonete',
            'caixa' => 'Caixa',
            'cozinha' => 'Coizinha',
        ];
        
        return view('users.edit', compact('user', 'roles'));
    }
    
    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();
        
        // Verificar permissões
        $canUpdate = false;
        if ($authUser->role === 'admin') {
            $canUpdate = true;
        } elseif ($authUser->role === 'gerente' && $user->role !== 'admin') {
            $canUpdate = true;
        } elseif ($authUser->id === $user->id) {
            $canUpdate = true;
        }
        
        if (!$canUpdate) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,gerente,garcom,caixa,cozinha',
            'active' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14|unique:users,cpf,' . $user->id,
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
        ]);
        
        // Restrições de permissão
        if ($authUser->role === 'gerente') {
            // Gerente não pode alterar role para admin
            if ($validated['role'] === 'admin') {
                return back()->withErrors([
                    'role' => 'Gerentes não podem alterar para administrador.'
                ])->withInput();
            }
            
            // Gerente não pode editar outros gerentes ou admin
            if ($user->role === 'admin' || ($user->role === 'gerente' && $user->id !== $authUser->id)) {
                abort(403, 'Acesso não autorizado.');
            }
        }
        
        // Se for edição do próprio perfil, não pode alterar role ou status
        if ($authUser->id === $user->id) {
            $validated['role'] = $user->role;
            $validated['active'] = true; // Não pode desativar a si mesmo
        }
        
        // Se for informada nova senha
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $validated['password'] = Hash::make($request->password);
        }
        
        $validated['active'] = $request->has('active');
        
        $user->update($validated);
        
        $redirectRoute = $authUser->id === $user->id ? 'profile' : 'users.index';
        $message = $authUser->id === $user->id ? 'Perfil atualizado com sucesso!' : 'Usuário atualizado com sucesso!';
        
        return redirect()->route($redirectRoute)
            ->with('success', $message);
    }
    
    public function destroy(User $user)
    {
        $authUser = Auth::user();
        
        // Não permitir deletar a si mesmo
        if ($user->id === $authUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'Você não pode excluir sua própria conta!');
        }
        
        // Verificar permissões
        $canDelete = false;
        if ($authUser->role === 'admin') {
            $canDelete = true;
        } elseif ($authUser->role === 'gerente' && !in_array($user->role, ['admin', 'gerente'])) {
            $canDelete = true;
        }
        
        if (!$canDelete) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'Usuário excluído com sucesso!');
    }
    
    public function toggleStatus(User $user)
    {
        $authUser = Auth::user();
        
        // Não permitir desativar a si mesmo
        if ($user->id === $authUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode desativar sua própria conta!'
            ]);
        }
        
        // Verificar permissões
        $canDelete = false;
        if ($authUser->role === 'admin') {
            $canDelete = true;
        } elseif ($authUser->role === 'gerente' && !in_array($user->role, ['admin', 'gerente'])) {
            $canDelete = true;
        }
        
        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para alterar o status deste usuário!'
            ]);
        }
        
        $user->update(['active' => !$user->active]);
        
        return response()->json([
            'success' => true,
            'message' => 'Status alterado com sucesso!',
            'active' => $user->active
        ]);
    }
    
    public function profile()
    {
        $user = Auth::user();
        return view('users.profile', compact('user'));
    }
    
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14|unique:users,cpf,' . $user->id,
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
        ]);
        
        // Atualizar senha se informada
        if ($request->filled('current_password')) {
            $request->validate([
                'current_password' => 'required|current_password',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            
            $validated['password'] = Hash::make($request->password);
        }
        
        $user->update($validated);
        
        return redirect()->route('profile')
            ->with('success', 'Perfil atualizado com sucesso!');
    }
    
    public function bulkActions(Request $request)
    {
        $authUser = Auth::user();
        
        if (!in_array($authUser->role, ['admin', 'gerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso não autorizado.'
            ]);
        }
        
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            $users = User::whereIn('id', $validated['users'])->get();
            $affectedCount = 0;
            
            foreach ($users as $user) {
                // Verificar permissões para cada usuário
                if ($user->id === $authUser->id) {
                    continue; // Pular o próprio usuário
                }
                
                // Verificar se tem permissão para esta ação
                $canModify = false;
                if ($authUser->role === 'admin') {
                    $canModify = true;
                } elseif ($authUser->role === 'gerente' && !in_array($user->role, ['admin', 'gerente'])) {
                    $canModify = true;
                }
                
                if ($canModify) {
                    switch ($validated['action']) {
                        case 'activate':
                            $user->update(['active' => true]);
                            $affectedCount++;
                            break;
                        case 'deactivate':
                            $user->update(['active' => false]);
                            $affectedCount++;
                            break;
                        case 'delete':
                            $user->delete();
                            $affectedCount++;
                            break;
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Ação realizada com sucesso! {$affectedCount} usuário(s) afetado(s)."
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar ação: ' . $e->getMessage()
            ]);
        }
    }
}