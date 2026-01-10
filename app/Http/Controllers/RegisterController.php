<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /**
     * Mostrar formulário de registro
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Processar registro - VERSÃO SIMPLIFICADA
     */
    public function register(Request $request)
    {
        // Validação incluindo tipo de conta, telefone, endereço e aceite dos termos
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            // valores vindos do select em auth.register: cliente, atendente, garcom, cozinha
            'role' => 'required|in:cliente,atendente,garcom,cozinha',
            'terms' => 'accepted',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Digite um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não coincide.',
            'role.required' => 'Selecione o tipo de conta.',
            'role.in' => 'Tipo de conta inválido.',
            'terms.accepted' => 'É necessário aceitar os termos de uso e política de privacidade.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('register')
                ->withErrors($validator)
                ->withInput();
        }

        // Dados validados
        $validated = $validator->validated();

        // Mapear os papéis da tela para os papéis usados no sistema
        // (admin/gerente continuam sendo criados apenas pelo painel interno)
        $roleMap = [
'cliente' => 'cliente', // cliente externo com papel próprio
            'atendente' => 'caixa',  // atende no balcão
            'garcom' => 'garcom',
            'cozinha' => 'cozinha',
        ];
        $dbRole = $roleMap[$validated['role']] ?? 'garcom';

        // Criar usuário preenchendo as colunas existentes
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $dbRole,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'active' => true,
        ]);

        // Fazer login automaticamente
        Auth::login($user);

        // 🔥 REDIRECIONAR PARA O DASHBOARD 🔥
        return redirect()->route('dashboard')->with('success', 'Conta criada com sucesso! Bem-vindo(a)!');
    }

    /**
     * Mostrar termos de uso (opcional)
     */
    public function showTerms()
    {
        return view('auth.terms', [
            'title' => 'Termos de Uso',
            'content' => '
                <h1>Termos de Uso - Sistema Restaurante</h1>
                <p>Última atualização: ' . date('d/m/Y') . '</p>

                <h2>1. Aceitação dos Termos</h2>
                <p>Ao se cadastrar no Sistema Restaurante, você concorda com estes termos de uso.</p>

                <h2>2. Uso do Sistema</h2>
                <p>O sistema é destinado para gestão de restaurantes e estabelecimentos alimentícios.</p>

                <h2>3. Responsabilidades</h2>
                <p>Você é responsável por manter a confidencialidade de sua senha.</p>

                <h2>4. Privacidade</h2>
                <p>Seus dados serão tratados conforme nossa Política de Privacidade.</p>
            '
        ]);
    }

    /**
     * Mostrar política de privacidade (opcional)
     */
    public function showPrivacy()
    {
        return view('auth.privacy', [
            'title' => 'Política de Privacidade',
            'content' => '
                <h1>Política de Privacidade - Sistema Restaurante</h1>
                <p>Última atualização: ' . date('d/m/Y') . '</p>

                <h2>1. Coleta de Dados</h2>
                <p>Coletamos apenas os dados necessários para o funcionamento do sistema: nome, e-mail e senha.</p>

                <h2>2. Uso dos Dados</h2>
                <p>Seus dados são utilizados exclusivamente para autenticação e operação do sistema.</p>

                <h2>3. Proteção</h2>
                <p>Implementamos medidas de segurança para proteger seus dados.</p>

                <h2>4. Cookies</h2>
                <p>Utilizamos cookies apenas para manter sua sessão ativa.</p>
            '
        ]);
    }

    /**
     * Verificar se e-mail já existe (para AJAX)
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');

        $exists = User::where('email', $email)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Este e-mail já está em uso.' : 'E-mail disponível.'
        ]);
    }

    /**
     * Verificar força da senha (para AJAX)
     */
    public function checkPasswordStrength(Request $request)
    {
        $password = $request->input('password');

        $strength = 0;
        $messages = [];

        // Verifica comprimento
        if (strlen($password) >= 8) {
            $strength += 25;
        } else {
            $messages[] = 'Mínimo 8 caracteres';
        }

        // Verifica se tem números
        if (preg_match('/[0-9]/', $password)) {
            $strength += 25;
        } else {
            $messages[] = 'Adicione números';
        }

        // Verifica se tem letras maiúsculas
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 25;
        } else {
            $messages[] = 'Adicione letras maiúsculas';
        }

        // Verifica se tem caracteres especiais
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $strength += 25;
        } else {
            $messages[] = 'Adicione caracteres especiais';
        }

        return response()->json([
            'strength' => $strength,
            'level' => $strength < 50 ? 'fraca' : ($strength < 75 ? 'média' : 'forte'),
            'messages' => $messages
        ]);
    }
}
