@extends('layouts.admin')

@section('title', 'Edit User')
@section('header', 'Edit User')
@section('subtitle', $user->full_name)

@section('content')
<!-- Error Messages -->
@if($errors->any())
<div class="card p-4 mb-6 border border-accent-900 bg-accent-900 bg-opacity-20">
    <div class="flex items-start">
        <i class="fas fa-exclamation-circle text-accent-400 mt-0.5 mr-3"></i>
        <div class="flex-1">
            <p class="text-sm font-semibold text-accent-300 mb-2">Please fix the following errors:</p>
            <ul class="text-xs text-accent-400 space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<div class="max-w-3xl mx-auto">
    <form action="{{ route('admin.users.update', $user) }}" method="POST" id="userForm">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="card p-6 mb-6 border border-primary-700">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-primary-400 mb-2">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="input-field text-sm @error('full_name') border-accent-400 @enderror" required>
                    @error('full_name')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input-field text-sm @error('email') border-accent-400 @enderror" required>
                    @error('email')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Phone Number</label>
                    <input type="tel" name="phone" id="phone_edit" value="{{ old('phone', $user->phone) }}" class="input-field text-sm @error('phone') border-accent-400 @enderror" placeholder="09123456789" maxlength="11" pattern="[0-9]{11}" inputmode="numeric">
                    @error('phone')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-primary-500 mt-1">
                        <i class="fas fa-phone mr-1"></i>11 digits (e.g., 09123456789)
                    </p>
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Role *</label>
                    <select name="role" class="input-field text-sm @error('role') border-accent-400 @enderror" required>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="rescuer" {{ old('role', $user->role) === 'rescuer' ? 'selected' : '' }}>Rescuer</option>
                        <option value="citizen" {{ old('role', $user->role) === 'citizen' ? 'selected' : '' }}>Citizen</option>
                    </select>
                    @error('role')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Password Change (Optional) -->
        <div class="card p-6 mb-6 border border-primary-700">
            <h3 class="text-sm font-semibold text-primary-100 mb-2">Change Password</h3>
            <p class="text-xs text-primary-400 mb-4">Leave blank to keep current password</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-primary-400 mb-2">New Password</label>
                    <input type="password" name="password" class="input-field text-sm @error('password') border-accent-400 @enderror" placeholder="Enter new password">
                    @error('password')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="input-field text-sm" placeholder="Confirm new password">
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button type="submit" id="submitBtn" class="bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2.5 rounded-lg transition text-sm font-medium">
                <i class="fas fa-save mr-2"></i><span id="btnText">Update User</span>
            </button>
            <a href="{{ route('admin.users.index') }}" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-6 py-2.5 rounded-lg transition text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('userForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    btn.disabled = true;
    btn.classList.add('opacity-50');
    btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
});

// Phone number: only allow numbers and limit to 11 digits
const phoneInput = document.getElementById('phone_edit');
if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        // Remove any non-numeric characters
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Limit to 11 digits
        if (this.value.length > 11) {
            this.value = this.value.slice(0, 11);
        }
    });
    
    // Prevent paste of non-numeric content
    phoneInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericOnly = pastedText.replace(/[^0-9]/g, '').slice(0, 11);
        this.value = numericOnly;
    });
}
</script>
@endsection
