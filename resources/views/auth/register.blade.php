<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema Restaurante</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .register-header {
            background: #28a745;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 30px 20px;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
        }
        .btn-outline-success:hover {
            background-color: #28a745;
            color: white;
        }
        .badge-role {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <!-- Cabeçalho -->
                    <div class="register-header text-center">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h1 class="h3">Criar Nova Conta</h1>
                        <p class="mb-0">Cadastre-se no Sistema Restaurante</p>
                    </div>
                    
                    <!-- Formulário -->
                    <div class="p-5">
                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            
                            <div class="row">
                                <!-- Nome Completo -->
                                <div class="col-md-6 mb-4">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-user me-2"></i>Nome Completo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           placeholder="Seu nome completo" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- E-mail -->
                                <div class="col-md-6 mb-4">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>E-mail <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email') }}" 
                                           placeholder="seu@email.com" 
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Senha -->
                                <div class="col-md-6 mb-4">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Senha <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Mínimo 8 caracteres" 
                                           required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                </div>
                                
                                <!-- Confirmar Senha -->
                                <div class="col-md-6 mb-4">
                                    <label for="password_confirmation" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Confirmar Senha <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           placeholder="Digite novamente" 
                                           required>
                                </div>
                                
                                <!-- Telefone -->
                                <div class="col-md-6 mb-4">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Telefone
                                    </label>
                                    <input type="tel" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone') }}" 
                                           placeholder="(11) 99999-9999">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Tipo de Usuário -->
                                <div class="col-md-6 mb-4">
                                    <label for="role" class="form-label">
                                        <i class="fas fa-user-tag me-2"></i>Tipo de Conta <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                        <option value="" disabled selected>Selecione uma opção</option>
                                        <option value="cliente" {{ old('role') == 'cliente' ? 'selected' : '' }}>
                                            Cliente
                                        </option>
                                        <option value="atendente" {{ old('role') == 'atendente' ? 'selected' : '' }}>
                                            Atendente
                                        </option>
                                        <option value="garcom" {{ old('role') == 'garcom' ? 'selected' : '' }}>
                                            Garçom
                                        </option>
                                        <option value="cozinha" {{ old('role') == 'cozinha' ? 'selected' : '' }}>
                                            Cozinha
                                        </option>
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    
                                    <!-- Descrições dos tipos -->
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <span class="badge bg-info badge-role">Cliente</span> - Fazer pedidos online
                                            <br>
                                            <span class="badge bg-primary badge-role">Atendente</span> - Atendimento no balcão
                                            <br>
                                            <span class="badge bg-warning badge-role">Garçom</span> - Atendimento nas mesas
                                            <br>
                                            <span class="badge bg-danger badge-role">Cozinha</span> - Preparo dos pedidos
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Endereço (apenas para cliente) -->
                                <div class="col-12 mb-4" id="address-field" style="display: none;">
                                    <label for="address" class="form-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Endereço (opcional)
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="address" 
                                           name="address" 
                                           value="{{ old('address') }}" 
                                           placeholder="Rua, número, bairro, cidade">
                                </div>
                            </div>
                            
                            <!-- Termos e Condições -->
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input @error('terms') is-invalid @enderror" 
                                       id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Concordo com os 
                                    <a href="{{ route('terms') }}" target="_blank" class="text-decoration-none">Termos de Uso</a> 
                                    e 
                                    <a href="{{ route('privacy') }}" target="_blank" class="text-decoration-none">Política de Privacidade</a>
                                    <span class="text-danger">*</span>
                                </label>
                                @error('terms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Botões -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Criar Conta
                                </button>
                                
                                <a href="{{ route('login') }}" class="btn btn-outline-success btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Já tenho conta
                                </a>
                            </div>
                        </form>
                        
                        <!-- Links extras -->
                        <div class="mt-4 text-center">
                            <p class="text-muted mb-0">
                                Ao se cadastrar, você concorda com nossos termos
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery e Máscara -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function(){
            // Máscara para telefone
            $('#phone').mask('(00) 00000-0000');
            
            // Mostrar/ocultar campo de endereço baseado no tipo de conta
            $('#role').change(function(){
                if($(this).val() === 'cliente') {
                    $('#address-field').slideDown();
                } else {
                    $('#address-field').slideUp();
                }
            });
            
            // Verificar valor atual ao carregar a página
            if($('#role').val() === 'cliente') {
                $('#address-field').show();
            }
        });
    </script>
</body>
</html>