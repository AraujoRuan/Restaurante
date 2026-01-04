@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Usuários do Sistema
                    </h5>
                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'gerente')
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Novo Usuário
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="Buscar usuário...">
                        </div>
                        <div class="col-md-3">
                            <select id="roleFilter" class="form-select">
                                <option value="">Todos os Cargos</option>
                                <option value="admin">Administrador</option>
                                <option value="gerente">Gerente</option>
                                <option value="garcom">Garçom</option>
                                <option value="caixa">Caixa</option>
                                <option value="cozinha">Cozinha</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">Todos os Status</option>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                <i class="fas fa-redo me-1"></i> Limpar Filtros
                            </button>
                        </div>
                    </div>
                    
                    <!-- Contador de Usuários -->
                    <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="userCount">{{ $users->total() }}</span> usuário(s) encontrado(s)
                        </div>
                        <div class="small">
                            <span class="badge bg-success">{{ $users->where('active', true)->count() }} Ativos</span>
                            <span class="badge bg-danger ms-2">{{ $users->where('active', false)->count() }} Inativos</span>
                        </div>
                    </div>
                    
                    <!-- Tabela de Usuários -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Cargo</th>
                                    <th>Telefone</th>
                                    <th>Status</th>
                                    <th>Cadastrado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                @php
                                    $authUser = Auth::user();
                                    
                                    // Verificar se pode editar
                                    $canEdit = false;
                                    if ($authUser->role === 'admin') {
                                        $canEdit = true;
                                    } elseif ($authUser->role === 'gerente' && $user->role !== 'admin') {
                                        $canEdit = true;
                                    } elseif ($authUser->id === $user->id) {
                                        $canEdit = true;
                                    }
                                    
                                    // Verificar se pode excluir/alterar status
                                    $canDelete = false;
                                    if ($authUser->id !== $user->id) {
                                        if ($authUser->role === 'admin') {
                                            $canDelete = true;
                                        } elseif ($authUser->role === 'gerente' && !in_array($user->role, ['admin', 'gerente'])) {
                                            $canDelete = true;
                                        }
                                    }
                                @endphp
                                
                                <tr data-role="{{ $user->role }}" data-status="{{ $user->active ? '1' : '0' }}">
                                    <td>
                                        @if($canDelete)
                                        <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                <span class="initials">{{ substr($user->name, 0, 2) }}</span>
                                            </div>
                                            <div>
                                                <strong>{{ $user->name }}</strong>
                                                @if($user->id === Auth::id())
                                                <span class="badge bg-info ms-1">Você</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'gerente' ? 'warning' : 'primary') }}">
                                            @switch($user->role)
                                                @case('admin') Administrador @break
                                                @case('gerente') Gerente @break
                                                @case('garcom') Garçom @break
                                                @case('caixa') Caixa @break
                                                @case('cozinha') Cozinha @break
                                            @endswitch
                                        </span>
                                    </td>
                                    <td>{{ $user->phone ?? 'Não informado' }}</td>
                                    <td>
                                        @if($user->active)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i> Ativo
                                        </span>
                                        @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i> Inativo
                                        </span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('users.show', $user) }}" class="btn btn-info" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($canEdit)
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endif
                                            
                                            @if($canDelete)
                                            <!-- Botão para alterar status -->
                                            <button type="button" 
                                                    class="btn btn-{{ $user->active ? 'danger' : 'success' }} btn-status" 
                                                    data-user-id="{{ $user->id }}"
                                                    title="{{ $user->active ? 'Desativar' : 'Ativar' }}">
                                                <i class="fas fa-{{ $user->active ? 'ban' : 'check' }}"></i>
                                            </button>
                                            
                                            <!-- Formulário de exclusão -->
                                            <form id="delete-form-{{ $user->id }}" 
                                                  action="{{ route('users.destroy', $user) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn btn-danger btn-delete" 
                                                        data-user-id="{{ $user->id }}"
                                                        title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                        <h5>Nenhum usuário encontrado</h5>
                                        <p class="text-muted">Comece criando um novo usuário</p>
                                        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'gerente')
                                        <a href="{{ route('users.create') }}" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus me-1"></i> Criar Primeiro Usuário
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Ações em Massa -->
                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'gerente')
                    <div class="card mt-3" id="bulkActionsCard" style="display: none;">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-tasks me-2"></i>
                                    <span id="selectedCount">0</span> usuário(s) selecionado(s)
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success" onclick="bulkAction('activate')">
                                        <i class="fas fa-check-circle me-1"></i> Ativar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="bulkAction('deactivate')">
                                        <i class="fas fa-ban me-1"></i> Desativar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                                        <i class="fas fa-trash me-1"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Paginação -->
                    @if($users->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Mostrando {{ $users->firstItem() }} a {{ $users->lastItem() }} de {{ $users->total() }} usuários
                        </div>
                        <div>
                            {{ $users->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalMessage">
                <!-- Mensagem será inserida via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-circle .initials {
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .user-checkbox {
        cursor: pointer;
    }
    #bulkActionsCard {
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@push('scripts')
<script>
// Variáveis globais
let selectedUsers = [];

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    setupFilters();
    setupButtonListeners();
    setupCheckboxes();
    updateUserCount();
});

// Configurar filtros
function setupFilters() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value;
        const selectedStatus = statusFilter.value;
        
        let visibleCount = 0;
        
        document.querySelectorAll('#usersTable tbody tr').forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const role = row.getAttribute('data-role');
            const status = row.getAttribute('data-status');
            
            const nameMatch = name.includes(searchTerm) || email.includes(searchTerm);
            const roleMatch = !selectedRole || role === selectedRole;
            const statusMatch = !selectedStatus || status === selectedStatus;
            
            const isVisible = nameMatch && roleMatch && statusMatch;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleCount++;
            }
        });
        
        document.getElementById('userCount').textContent = visibleCount;
        updateUserCount();
    }
    
    searchInput.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);
}

// Configurar listeners dos botões
function setupButtonListeners() {
    // Botões de status
    document.querySelectorAll('.btn-status').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            toggleUserStatus(userId);
        });
    });
    
    // Botões de exclusão
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            confirmDelete(userId);
        });
    });
}

// Configurar checkboxes
function setupCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const bulkActionsCard = document.getElementById('bulkActionsCard');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                updateSelectedUsers();
            });
        });
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedUsers);
    });
    
    function updateSelectedUsers() {
        selectedUsers = [];
        let checkedCount = 0;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.checked && checkbox.closest('tr').style.display !== 'none') {
                selectedUsers.push(checkbox.value);
                checkedCount++;
            }
        });
        
        // Atualizar contador
        document.getElementById('selectedCount').textContent = checkedCount;
        
        // Mostrar/ocultar ações em massa
        if (bulkActionsCard) {
            bulkActionsCard.style.display = checkedCount > 0 ? 'block' : 'none';
        }
        
        // Atualizar checkbox "Selecionar todos"
        if (selectAll) {
            const visibleCheckboxes = Array.from(checkboxes).filter(cb => 
                cb.closest('tr').style.display !== 'none'
            );
            selectAll.checked = visibleCheckboxes.length > 0 && 
                               visibleCheckboxes.every(cb => cb.checked);
            selectAll.indeterminate = checkedCount > 0 && checkedCount < visibleCheckboxes.length;
        }
    }
}

// Alternar status do usuário
function toggleUserStatus(userId) {
    showModal(
        'Alterar Status',
        'Tem certeza que deseja alterar o status deste usuário?',
        'Alterar',
        'btn-primary',
        function() {
            fetch(`/users/${userId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao processar requisição');
            });
        }
    );
}

// Confirmar exclusão
function confirmDelete(userId) {
    showModal(
        'Excluir Usuário',
        `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção!</strong><br>
            Esta ação não pode ser desfeita. O usuário será removido permanentemente do sistema.
        </div>
        <p>Tem certeza que deseja excluir este usuário?</p>
        `,
        'Excluir',
        'btn-danger',
        function() {
            document.getElementById(`delete-form-${userId}`).submit();
        }
    );
}

// Ações em massa
function bulkAction(action) {
    if (selectedUsers.length === 0) {
        showToast('warning', 'Selecione pelo menos um usuário');
        return;
    }
    
    let title, message, buttonText;
    
    switch (action) {
        case 'activate':
            title = 'Ativar Usuários';
            message = `Tem certeza que deseja ativar ${selectedUsers.length} usuário(s) selecionado(s)?`;
            buttonText = 'Ativar';
            break;
        case 'deactivate':
            title = 'Desativar Usuários';
            message = `Tem certeza que deseja desativar ${selectedUsers.length} usuário(s) selecionado(s)?`;
            buttonText = 'Desativar';
            break;
        case 'delete':
            title = 'Excluir Usuários';
            message = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção!</strong><br>
                    Esta ação não pode ser desfeita. ${selectedUsers.length} usuário(s) serão removidos permanentemente.
                </div>
                <p>Tem certeza que deseja excluir os usuários selecionados?</p>
            `;
            buttonText = 'Excluir';
            break;
    }
    
    showModal(
        title,
        message,
        buttonText,
        action === 'delete' ? 'btn-danger' : 'btn-primary',
        function() {
            fetch('/users/bulk-actions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    action: action,
                    users: selectedUsers
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao processar ação em massa');
            });
        }
    );
}

// Função para mostrar modal
function showModal(title, message, buttonText, buttonClass, confirmCallback) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmButton = document.getElementById('confirmButton');
    
    modalTitle.textContent = title;
    modalMessage.innerHTML = message;
    confirmButton.textContent = buttonText;
    confirmButton.className = `btn ${buttonClass}`;
    
    // Remover listeners anteriores
    const newConfirmButton = confirmButton.cloneNode(true);
    confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
    
    // Adicionar novo listener
    document.getElementById('confirmButton').addEventListener('click', function() {
        confirmCallback();
        modal.hide();
    });
    
    modal.show();
}

// Função para mostrar toast (notificação)
function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        createToastContainer();
    }
    
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('id', toastId);
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remover toast após ser escondido
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Criar container para toasts
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1060';
    document.body.appendChild(container);
}

// Limpar filtros
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('selectAll').checked = false;
    
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('bulkActionsCard').style.display = 'none';
    selectedUsers = [];
    
    // Refiltrar tabela
    const event = new Event('input');
    document.getElementById('searchInput').dispatchEvent(event);
}

// Atualizar contador de usuários
function updateUserCount() {
    const visibleRows = document.querySelectorAll('#usersTable tbody tr[style=""]').length;
    const totalRows = document.querySelectorAll('#usersTable tbody tr').length;
    
    if (visibleRows === 0 && totalRows > 0) {
        document.getElementById('userCount').textContent = '0';
        document.getElementById('userCount').parentElement.classList.add('alert-warning');
        document.getElementById('userCount').parentElement.classList.remove('alert-info');
    } else {
        document.getElementById('userCount').parentElement.classList.add('alert-info');
        document.getElementById('userCount').parentElement.classList.remove('alert-warning');
    }
}
</script>
@endpush

<!-- Container para Toasts (será criado dinamicamente) -->
<div id="toastContainer"></div>
@endsection
